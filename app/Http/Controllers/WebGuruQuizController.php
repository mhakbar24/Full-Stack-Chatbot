<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\Teacher;
use App\Services\GeminiService;
use App\Services\PhaseFWebCompetencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebGuruQuizController extends Controller
{
    public function index(Request $request)
    {
        $teacher = $this->resolveTeacher();
        $competencyService = app(PhaseFWebCompetencyService::class);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));

        $quizzes = Quiz::query()
            ->where('teacher_id', $teacher->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->when(!empty($validated['from']), function ($query) use ($validated) {
                $query->whereDate('created_at', '>=', $validated['from']);
            })
            ->when(!empty($validated['to']), function ($query) use ($validated) {
                $query->whereDate('created_at', '<=', $validated['to']);
            })
            ->withCount('questions')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('guru-quizzes', [
            'quizzes' => $quizzes,
            'filters' => $validated,
            'competencyOptions' => $competencyService->competencyOptions(),
            'user' => auth()->user(),
        ]);
    }

    public function store(Request $request)
    {
        $teacher = $this->resolveTeacher();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('guru.quizzes')->with('status', 'Quiz berhasil dibuat.');
    }

    public function generateAi(Request $request, GeminiService $gemini, PhaseFWebCompetencyService $competencyService)
    {
        $teacher = $this->resolveTeacher();

        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
            'competency_key' => ['nullable', 'string', 'max:100'],
            'question_count' => ['nullable', 'integer', 'min:5', 'max:20'],
            'difficulty' => ['nullable', 'in:dasar,menengah,lanjut'],
        ]);

        $topic = trim((string) $validated['topic']);
        $questionCount = (int) ($validated['question_count'] ?? 10);
        $difficulty = (string) ($validated['difficulty'] ?? 'menengah');
        $competencyKey = $validated['competency_key'] ?? null;
        $selectedCompetency = $competencyService->getCompetencyByKey($competencyKey);
        $catalogContext = $competencyService->renderCatalogContext($competencyKey);

        $prompt = "Buat paket quiz pemrograman website fase F berdasarkan topik: {$topic}.\n"
            . "Tingkat kesulitan: {$difficulty}. Jumlah soal: {$questionCount}.\n"
            . "Acuan CP/ATP yang harus dipatuhi:\n{$catalogContext}\n\n"
            . ($selectedCompetency ? "Kompetensi prioritas: {$selectedCompetency['name']}.\n" : '')
            . "Kembalikan output hanya JSON valid (tanpa markdown) dengan struktur:\n"
            . "{\n"
            . "  \"title\": \"...\",\n"
            . "  \"description\": \"...\",\n"
            . "  \"questions\": [\n"
            . "    {\n"
            . "      \"question_text\": \"...\",\n"
            . "      \"option_a\": \"...\",\n"
            . "      \"option_b\": \"...\",\n"
            . "      \"option_c\": \"...\",\n"
            . "      \"option_d\": \"...\",\n"
            . "      \"correct_answer\": \"A|B|C|D\"\n"
            . "    }\n"
            . "  ]\n"
            . "}\n"
            . "Semua soal harus relevan dengan CP/ATP dan topik.";

        $raw = $gemini->generateText(
            $prompt,
            'Kamu penyusun asesmen pembelajaran SMK fase F. Pastikan soal valid, jelas, dan sesuai CP/ATP pemrograman web.',
            true
        );

        if (!$raw) {
            return redirect()->route('guru.quizzes')->with('status', 'Generate quiz AI gagal. Periksa konfigurasi Gemini.');
        }

        $payload = $this->parseQuizJson($raw);
        if (!$this->isValidGeneratedQuizPayload($payload)) {
            $fallbackPrompt = "Buat quiz topik {$topic}. Balas HANYA JSON valid tanpa markdown/teks lain dengan format ini: "
                . '{"title":"...","description":"...","questions":[{"question_text":"...","option_a":"...","option_b":"...","option_c":"...","option_d":"...","correct_answer":"A"}]}'
                . " Jumlah soal {$questionCount}, level {$difficulty}, dan correct_answer wajib A/B/C/D.";

            $rawFallback = $gemini->generateText(
                $fallbackPrompt,
                'Jawab JSON murni saja. Jangan tulis bullet, catatan, atau markdown.',
                true
            );

            if ($rawFallback) {
                $raw = $rawFallback;
                $payload = $this->parseQuizJson($rawFallback);
            }
        }

        if (!$this->isValidGeneratedQuizPayload($payload)) {
            return redirect()->route('guru.quizzes')->with('status', 'Output AI tidak valid. Silakan coba lagi dengan topik lebih spesifik.');
        }

        $quiz = DB::transaction(function () use ($payload, $teacher) {
            $quiz = Quiz::create([
                'teacher_id' => $teacher->id,
                'title' => mb_substr((string) $payload['title'], 0, 255),
                'description' => isset($payload['description']) ? (string) $payload['description'] : null,
            ]);

            foreach ($payload['questions'] as $item) {
                $item = $this->normalizeQuestionItem(is_array($item) ? $item : []);

                $correct = strtoupper((string) ($item['correct_answer'] ?? ''));
                if (!in_array($correct, ['A', 'B', 'C', 'D'], true)) {
                    continue;
                }

                $questionText = trim((string) ($item['question_text'] ?? ''));
                $a = trim((string) ($item['option_a'] ?? ''));
                $b = trim((string) ($item['option_b'] ?? ''));
                $c = trim((string) ($item['option_c'] ?? ''));
                $d = trim((string) ($item['option_d'] ?? ''));
                if ($questionText === '' || $a === '' || $b === '' || $c === '' || $d === '') {
                    continue;
                }

                $quiz->questions()->create([
                    'question_text' => mb_substr($questionText, 0, 255),
                    'option_a' => mb_substr($a, 0, 255),
                    'option_b' => mb_substr($b, 0, 255),
                    'option_c' => mb_substr($c, 0, 255),
                    'option_d' => mb_substr($d, 0, 255),
                    'correct_answer' => $correct,
                    'difficulty_level' => $this->normalizeDifficulty((string) ($item['difficulty_level'] ?? $difficulty)),
                    'competency_key' => $this->normalizeCompetency((string) ($item['competency_key'] ?? ($selectedCompetency['key'] ?? ''))),
                ]);
            }

            return $quiz;
        });

        if ($quiz->questions()->count() === 0) {
            $quiz->delete();
            return redirect()->route('guru.quizzes')->with('status', 'Soal hasil AI tidak memenuhi format minimal. Coba lagi.');
        }

        return redirect()->route('guru.quizzes.show', $quiz->id)
            ->with('status', 'Quiz dan soal berhasil digenerate AI sesuai acuan CP/ATP.');
    }

    public function show(int $id)
    {
        $teacher = $this->resolveTeacher();
        $competencyService = app(PhaseFWebCompetencyService::class);

        $quiz = Quiz::with('questions')
            ->where('teacher_id', $teacher->id)
            ->findOrFail($id);

        return view('guru-quiz-detail', [
            'quiz' => $quiz,
            'competencyOptions' => $competencyService->competencyOptions(),
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $teacher = $this->resolveTeacher();

        $quiz = Quiz::where('teacher_id', $teacher->id)->findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $quiz->update($validated);

        return back()->with('status', 'Quiz berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $teacher = $this->resolveTeacher();

        $quiz = Quiz::where('teacher_id', $teacher->id)->findOrFail($id);
        $quiz->delete();

        return redirect()->route('guru.quizzes')->with('status', 'Quiz berhasil dihapus.');
    }

    public function storeQuestion(Request $request, int $quizId)
    {
        $teacher = $this->resolveTeacher();

        $quiz = Quiz::where('teacher_id', $teacher->id)->findOrFail($quizId);

        $validated = $request->validate([
            'question_text' => ['required', 'string', 'max:255'],
            'option_a' => ['required', 'string', 'max:255'],
            'option_b' => ['required', 'string', 'max:255'],
            'option_c' => ['required', 'string', 'max:255'],
            'option_d' => ['required', 'string', 'max:255'],
            'correct_answer' => ['required', 'in:A,B,C,D'],
            'difficulty_level' => ['nullable', 'in:dasar,menengah,lanjut'],
            'competency_key' => ['nullable', 'string', 'max:100'],
        ]);

        $validated['difficulty_level'] = $this->normalizeDifficulty((string) ($validated['difficulty_level'] ?? 'menengah'));
        $validated['competency_key'] = $this->normalizeCompetency((string) ($validated['competency_key'] ?? ''));

        $quiz->questions()->create($validated);

        return back()->with('status', 'Soal berhasil ditambahkan.');
    }

    public function updateQuestion(Request $request, int $id)
    {
        $teacher = $this->resolveTeacher();

        $question = Question::with('quiz')
            ->whereHas('quiz', fn ($q) => $q->where('teacher_id', $teacher->id))
            ->findOrFail($id);

        $validated = $request->validate([
            'question_text' => ['required', 'string', 'max:255'],
            'option_a' => ['required', 'string', 'max:255'],
            'option_b' => ['required', 'string', 'max:255'],
            'option_c' => ['required', 'string', 'max:255'],
            'option_d' => ['required', 'string', 'max:255'],
            'correct_answer' => ['required', 'in:A,B,C,D'],
            'difficulty_level' => ['nullable', 'in:dasar,menengah,lanjut'],
            'competency_key' => ['nullable', 'string', 'max:100'],
        ]);

        $validated['difficulty_level'] = $this->normalizeDifficulty((string) ($validated['difficulty_level'] ?? 'menengah'));
        $validated['competency_key'] = $this->normalizeCompetency((string) ($validated['competency_key'] ?? ''));

        $question->update($validated);

        return back()->with('status', 'Soal berhasil diperbarui.');
    }

    public function destroyQuestion(int $id)
    {
        $teacher = $this->resolveTeacher();

        $question = Question::whereHas('quiz', fn ($q) => $q->where('teacher_id', $teacher->id))
            ->findOrFail($id);

        $question->delete();

        return back()->with('status', 'Soal berhasil dihapus.');
    }

    private function resolveTeacher(): Teacher
    {
        $teacher = Teacher::where('email', auth()->user()->email)->first();

        if (!$teacher) {
            abort(403, 'Akun web belum terhubung ke data guru API.');
        }

        return $teacher;
    }

    private function parseQuizJson(string $raw): ?array
    {
        $clean = trim($raw);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
        $clean = preg_replace('/\s*```$/', '', (string) $clean);
        $clean = str_replace(["\xE2\x80\x9C", "\xE2\x80\x9D", "\xE2\x80\x98", "\xE2\x80\x99"], ['"', '"', "'", "'"], (string) $clean);

        $decoded = json_decode((string) $clean, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $this->normalizeQuizPayload($decoded);
        }

        if (preg_match('/\{[\s\S]*\}/', (string) $clean, $match)) {
            $candidate = preg_replace('/,\s*([}\]])/', '$1', $match[0]);
            $decoded = json_decode((string) $candidate, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->normalizeQuizPayload($decoded);
            }
        }

        return null;
    }

    private function normalizeQuizPayload(array $payload): ?array
    {
        $title = trim((string) ($payload['title'] ?? $payload['quiz_title'] ?? $payload['judul'] ?? ''));
        $description = (string) ($payload['description'] ?? $payload['desc'] ?? $payload['deskripsi'] ?? '');

        $questions = $payload['questions']
            ?? $payload['soal']
            ?? $payload['items']
            ?? $payload['data']['questions']
            ?? null;

        if (!is_array($questions)) {
            return null;
        }

        if ($title === '') {
            $title = 'Quiz AI: ' . now()->format('Y-m-d H:i');
        }

        $normalizedQuestions = [];
        foreach ($questions as $question) {
            if (!is_array($question)) {
                continue;
            }

            $normalizedQuestions[] = $this->normalizeQuestionItem($question);
        }

        return [
            'title' => $title,
            'description' => $description,
            'questions' => $normalizedQuestions,
        ];
    }

    private function normalizeQuestionItem(array $item): array
    {
        $choices = $item['choices'] ?? $item['options'] ?? null;

        if (is_array($choices)) {
            $optionA = (string) ($choices['A'] ?? $choices['a'] ?? $choices[0] ?? $item['option_a'] ?? $item['a'] ?? '');
            $optionB = (string) ($choices['B'] ?? $choices['b'] ?? $choices[1] ?? $item['option_b'] ?? $item['b'] ?? '');
            $optionC = (string) ($choices['C'] ?? $choices['c'] ?? $choices[2] ?? $item['option_c'] ?? $item['c'] ?? '');
            $optionD = (string) ($choices['D'] ?? $choices['d'] ?? $choices[3] ?? $item['option_d'] ?? $item['d'] ?? '');
        } else {
            $optionA = (string) ($item['option_a'] ?? $item['a'] ?? '');
            $optionB = (string) ($item['option_b'] ?? $item['b'] ?? '');
            $optionC = (string) ($item['option_c'] ?? $item['c'] ?? '');
            $optionD = (string) ($item['option_d'] ?? $item['d'] ?? '');
        }

        $correct = strtoupper(trim((string) (
            $item['correct_answer']
            ?? $item['answer']
            ?? $item['correct']
            ?? $item['kunci']
            ?? ''
        )));

        if (!in_array($correct, ['A', 'B', 'C', 'D'], true)) {
            $aliases = [
                'OPTION_A' => 'A',
                'OPTION_B' => 'B',
                'OPTION_C' => 'C',
                'OPTION_D' => 'D',
                'A.' => 'A',
                'B.' => 'B',
                'C.' => 'C',
                'D.' => 'D',
                '1' => 'A',
                '2' => 'B',
                '3' => 'C',
                '4' => 'D',
            ];
            $correct = $aliases[$correct] ?? $correct;
        }

        if (!in_array($correct, ['A', 'B', 'C', 'D'], true)) {
            $resolved = [
                strtoupper(trim($optionA)) => 'A',
                strtoupper(trim($optionB)) => 'B',
                strtoupper(trim($optionC)) => 'C',
                strtoupper(trim($optionD)) => 'D',
            ];
            $correct = $resolved[strtoupper(trim($correct))] ?? '';
        }

        return [
            'question_text' => (string) ($item['question_text'] ?? $item['question'] ?? $item['text'] ?? $item['soal'] ?? ''),
            'option_a' => $optionA,
            'option_b' => $optionB,
            'option_c' => $optionC,
            'option_d' => $optionD,
            'correct_answer' => $correct,
            'difficulty_level' => $this->normalizeDifficulty((string) ($item['difficulty_level'] ?? $item['difficulty'] ?? $item['level'] ?? 'menengah')),
            'competency_key' => $this->normalizeCompetency((string) ($item['competency_key'] ?? $item['competency'] ?? $item['cp_atp_key'] ?? '')),
        ];
    }

    private function normalizeDifficulty(string $difficulty): string
    {
        $value = strtolower(trim($difficulty));

        if (in_array($value, ['dasar', 'beginner', 'easy'], true)) {
            return 'dasar';
        }

        if (in_array($value, ['lanjut', 'advanced', 'hard'], true)) {
            return 'lanjut';
        }

        return 'menengah';
    }

    private function normalizeCompetency(string $key): ?string
    {
        $value = trim($key);
        return $value === '' ? null : mb_substr($value, 0, 100);
    }

    private function isValidGeneratedQuizPayload(?array $payload): bool
    {
        return is_array($payload)
            && !empty($payload['title'])
            && !empty($payload['questions'])
            && is_array($payload['questions']);
    }
}
