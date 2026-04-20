<?php

namespace App\Http\Controllers;

use App\Models\Materi;
use App\Models\Teacher;
use App\Services\GeminiService;
use App\Services\PhaseFWebCompetencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WebGuruMateriController extends Controller
{
    public function index(Request $request)
    {
        $teacher = $this->resolveTeacher();
        $competencyService = app(PhaseFWebCompetencyService::class);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));

        $materis = Materi::query()
            ->where('teacher_id', $teacher->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->when(!empty($validated['category']), function ($query) use ($validated) {
                $query->where('category', 'like', "%{$validated['category']}%");
            })
            ->when(!empty($validated['from']), function ($query) use ($validated) {
                $query->whereDate('created_at', '>=', $validated['from']);
            })
            ->when(!empty($validated['to']), function ($query) use ($validated) {
                $query->whereDate('created_at', '<=', $validated['to']);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('guru-materis', [
            'materis' => $materis,
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
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('materi_images', 'public');
        }

        Materi::create([
            'teacher_id' => $teacher->id,
            'title' => $validated['title'],
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'] ?? null,
            'image' => $imagePath,
        ]);

        return redirect()->route('guru.materis')->with('status', 'Materi berhasil dibuat.');
    }

    public function generateAi(Request $request, GeminiService $gemini, PhaseFWebCompetencyService $competencyService)
    {
        $teacher = $this->resolveTeacher();

        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
            'competency_key' => ['nullable', 'string', 'max:100'],
            'depth' => ['nullable', 'in:dasar,menengah,lanjut'],
        ]);

        $topic = trim((string) $validated['topic']);
        $depth = (string) ($validated['depth'] ?? 'menengah');
        $competencyKey = $validated['competency_key'] ?? null;
        $selectedCompetency = $competencyService->getCompetencyByKey($competencyKey);
        $catalogContext = $competencyService->renderCatalogContext($competencyKey);

        $prompt = "Buat materi pembelajaran pemrograman web fase F untuk guru dengan topik: {$topic}.\n"
            . "Kedalaman materi: {$depth}.\n"
            . "Acuan CP/ATP:\n{$catalogContext}\n\n"
            . ($selectedCompetency ? "Kompetensi prioritas: {$selectedCompetency['name']}.\n" : '')
            . "Kembalikan HANYA JSON valid tanpa markdown dengan struktur:\n"
            . "{\n"
            . "  \"title\": \"...\",\n"
            . "  \"category\": \"...\",\n"
            . "  \"description\": \"...\"\n"
            . "}\n"
            . "Isi description harus siap dipakai sebagai materi singkat yang jelas, runtut, dan aplikatif.";

        $raw = $gemini->generateText(
            $prompt,
            'Kamu penyusun materi SMK fase F. Jawab JSON murni saja tanpa teks tambahan.',
            true
        );

        $payload = $raw ? $this->parseMateriJson($raw) : null;

        if (!$this->isValidGeneratedMateriPayload($payload)) {
            $fallbackPrompt = "Topik materi: {$topic}. Balas HANYA JSON valid: "
                . '{"title":"...","category":"...","description":"..."}'
                . " dengan konten sesuai CP/ATP pemrograman web fase F level {$depth}.";

            $rawFallback = $gemini->generateText(
                $fallbackPrompt,
                'JSON murni tanpa bullet dan tanpa markdown.',
                true
            );

            if ($rawFallback) {
                $payload = $this->parseMateriJson($rawFallback);
            }
        }

        if (!$this->isValidGeneratedMateriPayload($payload)) {
            return redirect()->route('guru.materis')
                ->with('status', 'Generate materi AI gagal. Coba topik yang lebih spesifik.');
        }

        Materi::create([
            'teacher_id' => $teacher->id,
            'title' => mb_substr((string) $payload['title'], 0, 255),
            'category' => mb_substr((string) ($payload['category'] ?? 'CP/ATP Fase F'), 0, 255),
            'description' => (string) $payload['description'],
            'image' => null,
        ]);

        $rubric = $this->buildMateriRubric((string) $payload['description']);

        return redirect()->route('guru.materis')
            ->with('status', 'Materi berhasil digenerate AI sesuai CP/ATP.')
            ->with('materi_ai_rubric', $rubric);
    }

    public function update(Request $request, int $id)
    {
        $teacher = $this->resolveTeacher();
        $materi = Materi::where('teacher_id', $teacher->id)->findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            if ($materi->image) {
                Storage::disk('public')->delete($materi->image);
            }
            $validated['image'] = $request->file('image')->store('materi_images', 'public');
        }

        $materi->update($validated);

        return back()->with('status', 'Materi berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $teacher = $this->resolveTeacher();
        $materi = Materi::where('teacher_id', $teacher->id)->findOrFail($id);

        if ($materi->image) {
            Storage::disk('public')->delete($materi->image);
        }

        $materi->delete();

        return redirect()->route('guru.materis')->with('status', 'Materi berhasil dihapus.');
    }

    private function resolveTeacher(): Teacher
    {
        $teacher = Teacher::where('email', auth()->user()->email)->first();

        if (!$teacher) {
            abort(403, 'Akun web belum terhubung ke data guru API.');
        }

        return $teacher;
    }

    private function parseMateriJson(string $raw): ?array
    {
        $clean = trim($raw);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
        $clean = preg_replace('/\s*```$/', '', (string) $clean);
        $clean = str_replace(["\xE2\x80\x9C", "\xE2\x80\x9D", "\xE2\x80\x98", "\xE2\x80\x99"], ['"', '"', "'", "'"], (string) $clean);

        $decoded = json_decode((string) $clean, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $this->normalizeMateriPayload($decoded);
        }

        if (preg_match('/\{[\s\S]*\}/', (string) $clean, $match)) {
            $candidate = preg_replace('/,\s*([}\]])/', '$1', $match[0]);
            $decoded = json_decode((string) $candidate, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->normalizeMateriPayload($decoded);
            }
        }

        return null;
    }

    private function normalizeMateriPayload(array $payload): array
    {
        return [
            'title' => (string) ($payload['title'] ?? $payload['judul'] ?? $payload['materi_title'] ?? ''),
            'category' => (string) ($payload['category'] ?? $payload['kategori'] ?? 'CP/ATP Fase F'),
            'description' => (string) ($payload['description'] ?? $payload['deskripsi'] ?? $payload['content'] ?? ''),
        ];
    }

    private function isValidGeneratedMateriPayload(?array $payload): bool
    {
        return is_array($payload)
            && trim((string) ($payload['title'] ?? '')) !== ''
            && trim((string) ($payload['description'] ?? '')) !== '';
    }

    private function buildMateriRubric(string $description): array
    {
        $text = mb_strtolower(trim($description), 'UTF-8');
        $wordCount = str_word_count(strip_tags($description));

        $clarity = $wordCount >= 120 ? 4 : ($wordCount >= 70 ? 3 : 2);

        $completenessKeywords = ['tujuan', 'langkah', 'contoh', 'latihan', 'ringkasan'];
        $hitCompleteness = 0;
        foreach ($completenessKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $hitCompleteness++;
            }
        }
        $completeness = min(5, max(2, $hitCompleteness));

        $alignmentKeywords = ['html', 'css', 'javascript', 'api', 'backend', 'cp', 'atp'];
        $hitAlignment = 0;
        foreach ($alignmentKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $hitAlignment++;
            }
        }
        $alignment = min(5, max(2, (int) ceil($hitAlignment / 2)));

        $total = round(($clarity + $completeness + $alignment) / 3, 2);

        return [
            'clarity' => $clarity,
            'completeness' => $completeness,
            'alignment' => $alignment,
            'total' => $total,
            'recommendation' => $total >= 4
                ? 'Siap dipakai. Bisa langsung dipublikasikan.'
                : 'Perlu review guru: perbaiki contoh, langkah, atau keterkaitan CP/ATP.',
        ];
    }
}
