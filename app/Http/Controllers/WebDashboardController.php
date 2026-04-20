<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Materi;
use App\Models\Quiz;
use App\Models\Student;
use App\Models\StudentInterventionPlan;
use App\Models\StudentMateriLog;
use App\Models\StudentProgressAnalysis;
use App\Models\StudentQuizResult;
use App\Models\Teacher;
use App\Models\User;
use App\Services\GeminiService;
use App\Services\PhaseFWebCompetencyService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class WebDashboardController extends Controller
{
    public function siswa()
    {
        $student = Student::where('email', auth()->user()->email)->firstOrFail();
        $competencyService = app(PhaseFWebCompetencyService::class);

        $quizResults = StudentQuizResult::with('quiz:id,title,teacher_id')
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->get();

        $totalAttempts = $quizResults->count();
        $averageScore = $totalAttempts > 0 ? round((float) $quizResults->avg('score'), 2) : 0.0;
        $bestScore = $totalAttempts > 0 ? (int) $quizResults->max('score') : 0;
        $latestScore = $totalAttempts > 0 ? (int) $quizResults->first()->score : 0;

        $recentQuizResults = $quizResults->take(8)->map(function (StudentQuizResult $result) {
            return [
                'quiz_title' => $result->quiz->title ?? 'Quiz tanpa judul',
                'score' => (int) $result->score,
                'taken_at' => $result->created_at,
            ];
        });

        $materiLogs = StudentMateriLog::with('materi:id,title,category,teacher_id')
            ->where('student_id', $student->id)
            ->orderByDesc('accessed_at')
            ->get();

        $accessedMateriIds = $materiLogs->pluck('materi_id')->unique();
        $totalMateriAvailable = Materi::count();
        $totalMateriAccessed = $accessedMateriIds->count();
        $materiCoverage = $totalMateriAvailable > 0
            ? round(($totalMateriAccessed / $totalMateriAvailable) * 100, 2)
            : 0.0;

        $recentMateriAccess = $materiLogs->take(8)->map(function (StudentMateriLog $log) {
            return [
                'materi_title' => $log->materi->title ?? 'Materi tanpa judul',
                'category' => $log->materi->category,
                'accessed_at' => $log->accessed_at,
            ];
        });

        $recommendedMateri = Materi::query()
            ->whereNotIn('id', $accessedMateriIds)
            ->orderByDesc('created_at')
            ->take(6)
            ->get(['id', 'title', 'category', 'created_at']);

        $adaptiveProfile = $this->calculateAdaptiveProfile($latestScore, $averageScore);

        $competencyProfile = $competencyService->buildProfile(
            $quizResults,
            $materiLogs->loadMissing('materi:id,title,category,description')
        );

        $learningPath = $this->buildLearningPathPlan(
            $adaptiveProfile,
            $competencyProfile,
            $recentQuizResults,
            $recentMateriAccess
        );

        return view('siswa', [
            'student' => $student,
            'stats' => [
                'total_attempts' => $totalAttempts,
                'average_score' => $averageScore,
                'best_score' => $bestScore,
                'latest_score' => $latestScore,
                'materi_accessed' => $totalMateriAccessed,
                'materi_coverage' => $materiCoverage,
            ],
            'recentQuizResults' => $recentQuizResults,
            'recentMateriAccess' => $recentMateriAccess,
            'recommendedMateri' => $recommendedMateri,
            'adaptiveProfile' => $adaptiveProfile,
            'learningPath' => $learningPath,
            'chatbotHistory' => session('siswa_chatbot_history', []),
            'user' => auth()->user(),
        ]);
    }

    public function siswaChatbot(Request $request, GeminiService $gemini, PhaseFWebCompetencyService $competencyService)
    {
        $student = Student::where('email', auth()->user()->email)->firstOrFail();

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:800'],
        ]);

        $userMessage = trim((string) $validated['message']);
        if ($userMessage === '') {
            return redirect()->route('siswa.dashboard')->with('chatbot_error', 'Pesan tidak boleh kosong.');
        }

        $history = collect(session('siswa_chatbot_history', []))
            ->take(-6)
            ->values();

        $data = $this->collectSiswaProgressOverview($student);

        $quizResults = StudentQuizResult::with('quiz:id,title,description,teacher_id')
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->take(15)
            ->get();

        $materiLogs = StudentMateriLog::with('materi:id,title,category,description')
            ->where('student_id', $student->id)
            ->orderByDesc('accessed_at')
            ->take(20)
            ->get();

        $competencyProfile = $competencyService->buildProfile($quizResults, $materiLogs);

        $quizContext = collect($data['recentAttempts'])->take(5)->map(function ($row) {
            return '- ' . $row['quiz_title'] . ': skor ' . $row['score'] . ' (' . $row['taken_at']->format('Y-m-d H:i') . ')';
        })->implode("\n");

        $materiContext = collect($data['recentAccesses'])->take(6)->map(function ($row) {
            return '- ' . $row['title'] . ' [' . $row['category'] . '] pada ' . $row['accessed_at']->format('Y-m-d H:i');
        })->implode("\n");

        $conversationContext = $history->map(function ($item) {
            $q = trim((string) ($item['question'] ?? ''));
            $a = trim((string) ($item['answer'] ?? ''));
            return "Siswa: {$q}\nAsisten: {$a}";
        })->implode("\n\n");

        $citationItems = $this->buildDashboardCitationItemsFromRecentAccesses($data['recentAccesses'] ?? []);

        $prompt = "Profil siswa:\n"
            . 'Nama: ' . $student->name . "\n"
            . 'Total quiz: ' . $data['summary']['total_attempts'] . "\n"
            . 'Rata-rata skor: ' . $data['summary']['average_score'] . "\n"
            . 'Cakupan materi: ' . $data['summary']['coverage_percent'] . "%\n\n"
            . "Quiz terakhir:\n" . ($quizContext !== '' ? $quizContext : '- Belum ada data quiz') . "\n\n"
            . "Materi yang sudah dibaca:\n" . ($materiContext !== '' ? $materiContext : '- Belum ada data materi') . "\n\n"
            . "Peta CP/ATP fase F:\n" . $competencyProfile['context_text'] . "\n\n"
            . ($conversationContext !== '' ? "Riwayat percakapan terbaru:\n{$conversationContext}\n\n" : '')
            . "Pertanyaan siswa saat ini: {$userMessage}\n\n"
            . "Jawab ringkas dan aplikatif untuk siswa. Wajib:"
            . "\n1) jawab pertanyaan utama"
            . "\n2) hubungkan dengan minimal satu quiz atau materi yang sudah dibaca"
            . "\n3) beri latihan kecil 1 langkah yang bisa dilakukan hari ini.";

        $reply = $gemini->generateText(
            $prompt,
            'Kamu mentor belajar pemrograman web untuk siswa SMK fase F. Gunakan bahasa Indonesia yang hangat, jelas, dan tidak menghakimi.'
        );

        if (!$reply) {
            return redirect()->route('siswa.dashboard')->with('chatbot_error', 'Chatbot sedang tidak tersedia. Coba lagi beberapa saat.');
        }

        $history->push([
            'question' => $userMessage,
            'answer' => trim($reply),
            'citations' => $citationItems,
            'created_at' => now()->toDateTimeString(),
        ]);

        session(['siswa_chatbot_history' => $history->take(-8)->values()->all()]);

        return redirect()->route('siswa.dashboard')->with('chatbot_success', 'Balasan chatbot berhasil dibuat.');
    }

    public function siswaProgress(Request $request)
    {
        $student = Student::where('email', auth()->user()->email)->firstOrFail();

        $validated = $request->validate([
            'weeks' => ['nullable', 'integer', 'min:4', 'max:16'],
        ]);

        $weeks = (int) ($validated['weeks'] ?? 8);
        $start = Carbon::now()->startOfWeek()->subWeeks($weeks - 1);
        $end = Carbon::now()->endOfWeek();

        $quizRows = StudentQuizResult::query()
            ->where('student_id', $student->id)
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get(['score', 'created_at']);

        $materiRows = StudentMateriLog::with('materi:id,category')
            ->where('student_id', $student->id)
            ->whereBetween('accessed_at', [$start, $end])
            ->orderBy('accessed_at')
            ->get(['materi_id', 'accessed_at']);

        $weekMap = collect();
        for ($i = 0; $i < $weeks; $i++) {
            $weekStart = $start->copy()->addWeeks($i)->startOfWeek();
            $key = $weekStart->format('o-W');
            $weekMap->put($key, [
                'week_key' => $key,
                'week_label' => $weekStart->translatedFormat('d M') . ' - ' . $weekStart->copy()->endOfWeek()->translatedFormat('d M'),
                'quiz_avg' => 0.0,
                'quiz_attempts' => 0,
                'materi_accesses' => 0,
            ]);
        }

        $quizGrouped = $quizRows->groupBy(function ($item) {
            return Carbon::parse($item->created_at)->startOfWeek()->format('o-W');
        });

        foreach ($quizGrouped as $key => $rows) {
            if ($weekMap->has($key)) {
                $entry = $weekMap->get($key);
                $entry['quiz_attempts'] = $rows->count();
                $entry['quiz_avg'] = round((float) $rows->avg('score'), 2);
                $weekMap->put($key, $entry);
            }
        }

        $materiGrouped = $materiRows->groupBy(function ($item) {
            return Carbon::parse($item->accessed_at)->startOfWeek()->format('o-W');
        });

        foreach ($materiGrouped as $key => $rows) {
            if ($weekMap->has($key)) {
                $entry = $weekMap->get($key);
                $entry['materi_accesses'] = $rows->count();
                $weekMap->put($key, $entry);
            }
        }

        $weeklyProgress = $weekMap->values();
        $maxQuizAvg = max(1, (float) $weeklyProgress->max('quiz_avg'));
        $maxMateriAccess = max(1, (int) $weeklyProgress->max('materi_accesses'));

        $categorySummary = $materiRows
            ->groupBy(function ($item) {
                return $item->materi->category ?? 'Tanpa Kategori';
            })
            ->map(function ($rows, $category) {
                return [
                    'category' => $category,
                    'access_count' => $rows->count(),
                    'unique_materi' => $rows->pluck('materi_id')->unique()->count(),
                ];
            })
            ->sortByDesc('access_count')
            ->values();

        return view('siswa-progress', [
            'student' => $student,
            'weeks' => $weeks,
            'weeklyProgress' => $weeklyProgress,
            'maxQuizAvg' => $maxQuizAvg,
            'maxMateriAccess' => $maxMateriAccess,
            'categorySummary' => $categorySummary,
            'user' => auth()->user(),
        ]);
    }

    public function siswaProgressOverview()
    {
        $student = Student::where('email', auth()->user()->email)->firstOrFail();

        $data = $this->collectSiswaProgressOverview($student);

        return view('siswa-progress-overview', [
            'student' => $student,
            'summary' => $data['summary'],
            'recentAttempts' => $data['recentAttempts'],
            'recentAccesses' => $data['recentAccesses'],
            'notYetViewedMateri' => $data['notYetViewedMateri'],
            'aiFeedback' => session('siswa_ai_feedback'),
            'user' => auth()->user(),
        ]);
    }

    public function siswaProgressOverviewAi(Request $request, GeminiService $gemini, PhaseFWebCompetencyService $competencyService)
    {
        $student = Student::where('email', auth()->user()->email)->firstOrFail();

        $validated = $request->validate([
            'context' => ['nullable', 'string', 'max:500'],
        ]);

        $data = $this->collectSiswaProgressOverview($student);
        $summary = $data['summary'];
        $recentSummary = collect($data['recentAttempts'])->take(5)->map(function ($item) {
            return "- {$item['quiz_title']}: skor {$item['score']} ({$item['taken_at']->format('Y-m-d H:i')})";
        })->implode("\n");

        $quizResults = StudentQuizResult::with('quiz:id,title,description,teacher_id')
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->get();

        $materiLogsForCompetency = StudentMateriLog::with('materi:id,title,category,description')
            ->where('student_id', $student->id)
            ->orderByDesc('accessed_at')
            ->get();

        $competencyProfile = $competencyService->buildProfile($quizResults, $materiLogsForCompetency);

        if ((int) $summary['total_attempts'] === 0) {
            return redirect()->route('siswa.progress.overview')->with('siswa_ai_feedback', [
                'status' => 'error',
                'message' => 'Belum ada hasil quiz untuk dianalisis oleh AI.',
                'analysis_sections' => null,
                'quality_badge' => null,
            ]);
        }

        $studentContext = trim((string) ($validated['context'] ?? ''));

        $prompt = "Berikut data perkembangan belajar saya sebagai siswa:\n"
            . "Nama: {$student->name}\n"
            . "Email: {$student->email}\n"
            . "Total quiz dikerjakan: {$summary['total_attempts']}\n"
            . "Rata-rata skor: {$summary['average_score']}\n"
            . "Skor terbaik: {$summary['best_score']}\n"
            . "Skor terbaru: {$summary['latest_score']}\n"
            . "Cakupan materi dibaca: {$summary['coverage_percent']}%\n"
            . "Ringkasan 5 quiz terakhir:\n{$recentSummary}\n\n"
            . "Peta keterhubungan soal dan materi berdasarkan CP/ATP Pemrograman Website Fase F:\n"
            . $competencyProfile['context_text'] . "\n\n"
            . ($studentContext !== '' ? "Konteks tambahan dari siswa: {$studentContext}\n\n" : '')
            . "Tolong buat feedback belajar dalam Bahasa Indonesia dengan format WAJIB persis ini:\n"
            . "Ringkasan: <isi>\n"
            . "Kekuatan: <isi>\n"
            . "Perlu Ditingkatkan: <isi>\n"
            . "Tindak Lanjut: <isi>\n"
            . "Setiap bagian 1-2 kalimat, suportif, jelas, praktis, dan menyinggung CP/ATP paling relevan. Total maksimal 220 kata.";

        $analysis = $gemini->generateText(
            $prompt,
            'Kamu adalah mentor belajar yang suportif. Berikan umpan balik yang membangun dan bisa langsung dipraktikkan siswa.'
        );

        if (!$analysis) {
            return redirect()->route('siswa.progress.overview')->with('siswa_ai_feedback', [
                'status' => 'error',
                'message' => 'Gagal mendapatkan feedback AI. Coba lagi beberapa saat.',
                'analysis_sections' => null,
                'quality_badge' => null,
            ]);
        }

        $analysisSections = $this->parseAnalysisSections($analysis);
        $qualityBadge = $this->calculateQualityBadge($analysisSections);

        $ownerTeacherId = StudentQuizResult::with('quiz:id,teacher_id')
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($item) {
                return $item->quiz->teacher_id ?? null;
            })
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        $savedAnalysis = null;
        if ($ownerTeacherId) {
            $savedAnalysis = StudentProgressAnalysis::create([
                'teacher_id' => (int) $ownerTeacherId,
                'student_id' => $student->id,
                'total_quiz' => (int) $summary['total_attempts'],
                'average_score' => (float) $summary['average_score'],
                'best_score' => (int) $summary['best_score'],
                'latest_score' => (int) $summary['latest_score'],
                'teacher_context' => $studentContext !== '' ? 'Konteks siswa: ' . $studentContext : null,
                'analysis' => $analysis,
            ]);
        }

        return redirect()->route('siswa.progress.overview')->with('siswa_ai_feedback', [
            'status' => 'success',
            'message' => 'Feedback AI berhasil dibuat.',
            'analysis_sections' => $analysisSections,
            'quality_badge' => $qualityBadge,
            'competency_profile' => $competencyProfile,
            'history_id' => $savedAnalysis?->id,
        ]);
    }

    public function siswaHistoryAi(Request $request)
    {
        $student = Student::where('email', auth()->user()->email)->firstOrFail();

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = StudentProgressAnalysis::query()
            ->where('student_id', $student->id)
            ->orderByDesc('created_at');

        if (!empty($validated['from'])) {
            $query->where('created_at', '>=', Carbon::parse($validated['from'])->startOfDay());
        }

        if (!empty($validated['to'])) {
            $query->where('created_at', '<=', Carbon::parse($validated['to'])->endOfDay());
        }

        $history = $query->paginate(10)->withQueryString();

        return view('siswa-ai-history', [
            'student' => $student,
            'history' => $history,
            'filters' => $validated,
            'user' => auth()->user(),
        ]);
    }

    public function siswaHistoryAiDetail(int $id)
    {
        $student = Student::where('email', auth()->user()->email)->firstOrFail();

        $analysis = StudentProgressAnalysis::query()
            ->where('id', $id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        $analysisSections = $this->parseAnalysisSections($analysis->analysis);
        $qualityBadge = $this->calculateQualityBadge($analysisSections);

        return view('siswa-ai-history-detail', [
            'student' => $student,
            'analysis' => $analysis,
            'analysisSections' => $analysisSections,
            'qualityBadge' => $qualityBadge,
            'user' => auth()->user(),
        ]);
    }

    public function siswaProgressOverviewPdf()
    {
        $student = Student::where('email', auth()->user()->email)->firstOrFail();
        $data = $this->collectSiswaProgressOverview($student);

        $pdf = Pdf::loadView('pdf.siswa-progress-overview', [
            'student' => $student,
            'summary' => $data['summary'],
            'recentAttempts' => $data['recentAttempts'],
            'recentAccesses' => $data['recentAccesses'],
            'notYetViewedMateri' => $data['notYetViewedMateri'],
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('progres-siswa-' . $student->id . '-' . now()->format('YmdHis') . '.pdf');
    }

    private function collectSiswaProgressOverview(Student $student): array
    {

        $results = StudentQuizResult::with('quiz:id,title')
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->get();

        $totalAttempts = $results->count();
        $averageScore = $totalAttempts > 0 ? round((float) $results->avg('score'), 2) : 0.0;
        $bestScore = $totalAttempts > 0 ? (int) $results->max('score') : 0;
        $latestScore = $totalAttempts > 0 ? (int) $results->first()->score : 0;

        $recentAttempts = $results->take(10)->values()->map(function ($item) {
            return [
                'quiz_title' => $item->quiz->title ?? 'Quiz tanpa judul',
                'score' => (int) $item->score,
                'taken_at' => $item->created_at,
            ];
        });

        $latestThreeAvg = round((float) $results->take(3)->avg('score'), 2);
        $previousThreeAvg = round((float) $results->slice(3, 3)->avg('score'), 2);
        $trend = 'stabil';
        if ($totalAttempts >= 4 && $latestThreeAvg > $previousThreeAvg) {
            $trend = 'meningkat';
        } elseif ($totalAttempts >= 4 && $latestThreeAvg < $previousThreeAvg) {
            $trend = 'menurun';
        }

        $totalMateri = Materi::count();
        $materiLogs = StudentMateriLog::with('materi:id,title,category')
            ->where('student_id', $student->id)
            ->orderByDesc('accessed_at')
            ->get();

        $totalAccessLogs = $materiLogs->count();
        $viewedMateriIds = $materiLogs->pluck('materi_id')->unique();
        $uniqueMateriAccessed = $viewedMateriIds->count();
        $coveragePercent = $totalMateri > 0
            ? round(($uniqueMateriAccessed / $totalMateri) * 100, 2)
            : 0.0;

        $recentAccesses = $materiLogs->take(10)->map(function ($item) {
            return [
                'title' => $item->materi->title ?? 'Materi tanpa judul',
                'category' => $item->materi->category ?? 'Tanpa Kategori',
                'accessed_at' => $item->accessed_at,
            ];
        });

        $notYetViewedMateri = Materi::query()
            ->whereNotIn('id', $viewedMateriIds)
            ->orderByDesc('created_at')
            ->take(6)
            ->get(['id', 'title', 'category', 'created_at']);

        return [
            'summary' => [
                'total_attempts' => $totalAttempts,
                'average_score' => $averageScore,
                'best_score' => $bestScore,
                'latest_score' => $latestScore,
                'trend' => $trend,
                'total_materi' => $totalMateri,
                'total_access_logs' => $totalAccessLogs,
                'unique_materi_accessed' => $uniqueMateriAccessed,
                'coverage_percent' => $coveragePercent,
            ],
            'recentAttempts' => $recentAttempts,
            'recentAccesses' => $recentAccesses,
            'notYetViewedMateri' => $notYetViewedMateri,
        ];
    }

    public function siswaMateris(Request $request)
    {
        $student = Student::where('email', auth()->user()->email)->firstOrFail();

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);

        $query = Materi::with('teacher:id,name,email')->orderByDesc('created_at');

        if (!empty($validated['q'])) {
            $q = trim((string) $validated['q']);
            $query->where(function ($builder) use ($q) {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%");
            });
        }

        if (!empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        $materis = $query->paginate(12)->withQueryString();
        $categories = Materi::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category');
        $accessedIds = StudentMateriLog::where('student_id', $student->id)->pluck('materi_id')->unique();

        return view('siswa-materis', [
            'student' => $student,
            'materis' => $materis,
            'categories' => $categories,
            'accessedIds' => $accessedIds,
            'filters' => [
                'q' => (string) ($validated['q'] ?? ''),
                'category' => (string) ($validated['category'] ?? ''),
            ],
            'user' => auth()->user(),
        ]);
    }

    public function siswaMateriShow(int $id)
    {
        $student = Student::where('email', auth()->user()->email)->firstOrFail();
        $materi = Materi::with('teacher:id,name,email')->findOrFail($id);

        StudentMateriLog::create([
            'student_id' => $student->id,
            'materi_id' => $materi->id,
            'accessed_at' => now(),
        ]);

        $relatedMateris = Materi::query()
            ->where('id', '!=', $materi->id)
            ->when($materi->category, function ($query) use ($materi) {
                $query->where('category', $materi->category);
            })
            ->orderByDesc('created_at')
            ->take(6)
            ->get(['id', 'title', 'category', 'created_at']);

        return view('siswa-materi-detail', [
            'student' => $student,
            'materi' => $materi,
            'relatedMateris' => $relatedMateris,
            'materiChatbotHistory' => session($this->materiChatSessionKey($materi->id), []),
            'user' => auth()->user(),
        ]);
    }

    public function siswaMateriChatbot(Request $request, int $id, GeminiService $gemini)
    {
        $student = Student::where('email', auth()->user()->email)->firstOrFail();
        $materi = Materi::with('teacher:id,name,email')->findOrFail($id);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:800'],
        ]);

        $userMessage = trim((string) $validated['message']);
        if ($userMessage === '') {
            return redirect()->route('siswa.materis.show', $materi->id)->with('chatbot_error', 'Pertanyaan tidak boleh kosong.');
        }

        $historyKey = $this->materiChatSessionKey($materi->id);
        $history = collect(session($historyKey, []))->take(-6)->values();

        $recentQuizResults = StudentQuizResult::with('quiz:id,title')
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(function ($row) {
                return '- ' . ($row->quiz->title ?? 'Quiz') . ': skor ' . (int) $row->score;
            })
            ->implode("\n");

        $recentMateriRows = StudentMateriLog::with('materi:id,title,category')
            ->where('student_id', $student->id)
            ->orderByDesc('accessed_at')
            ->take(6)
            ->get();

        $recentMateriReads = $recentMateriRows
            ->map(function ($row) {
                $title = $row->materi->title ?? 'Materi';
                $category = $row->materi->category ?? 'Umum';
                return '- ' . $title . ' [' . $category . ']';
            })
            ->implode("\n");

        $citationMateris = collect([$materi])
            ->merge($recentMateriRows->map(function ($row) {
                return $row->materi ?? null;
            })->filter())
            ->unique('id')
            ->values();

        $citationLines = $citationMateris->map(function ($item) {
            return '- ' . ($item->title ?? 'Materi') . ' [' . ($item->category ?? 'Umum') . ']';
        })->implode("\n");

        $conversationContext = $history->map(function ($item) {
            $q = trim((string) ($item['question'] ?? ''));
            $a = trim((string) ($item['answer'] ?? ''));
            return "Siswa: {$q}\nAsisten: {$a}";
        })->implode("\n\n");

        $prompt = "Konteks materi yang sedang dibaca siswa:\n"
            . 'Judul materi: ' . $materi->title . "\n"
            . 'Kategori: ' . ($materi->category ?: 'Umum') . "\n"
            . 'Isi materi:\n' . ($materi->description ?: '-') . "\n\n"
            . "Riwayat performa quiz siswa:\n" . ($recentQuizResults !== '' ? $recentQuizResults : '- Belum ada riwayat quiz') . "\n\n"
            . "Riwayat materi yang sudah dibaca siswa:\n" . ($recentMateriReads !== '' ? $recentMateriReads : '- Belum ada riwayat materi') . "\n\n"
            . "Sumber materi acuan untuk jawaban:\n" . ($citationLines !== '' ? $citationLines : '- Tidak ada') . "\n\n"
            . ($conversationContext !== '' ? "Riwayat percakapan sebelumnya:\n{$conversationContext}\n\n" : '')
            . "Pertanyaan siswa saat ini: {$userMessage}\n\n"
            . "Jawab dengan bahasa Indonesia yang ringkas, suportif, dan mudah dipahami siswa. WAJIB:"
            . "\n1) Jelaskan konsep dari materi saat ini"
            . "\n2) Hubungkan ke riwayat quiz/materi siswa jika relevan"
            . "\n3) Beri latihan kecil 1 langkah yang bisa langsung dicoba."
            . "\n4) Akhiri jawaban dengan bagian 'Sumber:' yang menyebut judul materi acuan.";

        $reply = $gemini->generateText(
            $prompt,
            'Kamu tutor belajar siswa SMK. Jelaskan konsep dengan sederhana dan aplikatif.'
        );

        if (!$reply) {
            return redirect()->route('siswa.materis.show', $materi->id)->with('chatbot_error', 'Chatbot tidak dapat merespons saat ini. Coba lagi.');
        }

        $citations = $this->buildCitationItemsFromMateris($citationMateris);

        $history->push([
            'question' => $userMessage,
            'answer' => trim($reply),
            'citations' => $citations,
            'created_at' => now()->toDateTimeString(),
        ]);

        session([$historyKey => $history->take(-8)->values()->all()]);

        return redirect()->route('siswa.materis.show', $materi->id)->with('chatbot_success', 'Balasan chatbot tersedia di bawah materi.');
    }

    public function siswaQuizzes(Request $request)
    {
        $student = Student::where('email', auth()->user()->email)->firstOrFail();

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $query = Quiz::with('teacher:id,name,email')
            ->withCount('questions')
            ->orderByDesc('created_at');

        if (!empty($validated['q'])) {
            $q = trim((string) $validated['q']);
            $query->where(function ($builder) use ($q) {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $quizzes = $query->paginate(10)->withQueryString();

        $latestResultsByQuiz = StudentQuizResult::query()
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('quiz_id')
            ->map(fn ($rows) => $rows->first());

        $latestScore = (int) optional($latestResultsByQuiz->first())->score;
        $averageScore = $latestResultsByQuiz->count() > 0
            ? (float) $latestResultsByQuiz->avg('score')
            : 0.0;
        $adaptiveProfile = $this->calculateAdaptiveProfile($latestScore, $averageScore);

        return view('siswa-quizzes', [
            'student' => $student,
            'quizzes' => $quizzes,
            'latestResultsByQuiz' => $latestResultsByQuiz,
            'adaptiveProfile' => $adaptiveProfile,
            'filters' => [
                'q' => (string) ($validated['q'] ?? ''),
            ],
            'user' => auth()->user(),
        ]);
    }

    public function siswaQuizShow(int $id)
    {
        $student = Student::where('email', auth()->user()->email)->firstOrFail();
        $quiz = Quiz::with(['teacher:id,name,email', 'questions'])->findOrFail($id);
        $competencyService = app(PhaseFWebCompetencyService::class);

        $latestResult = StudentQuizResult::query()
            ->where('student_id', $student->id)
            ->where('quiz_id', $quiz->id)
            ->latest()
            ->first();

        $adaptiveProfile = $this->calculateAdaptiveProfile(
            (int) ($latestResult->score ?? 0),
            (float) (StudentQuizResult::where('student_id', $student->id)->avg('score') ?? 0)
        );

        $historyQuizResults = StudentQuizResult::with('quiz:id,title,description,teacher_id')
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->take(40)
            ->get();

        $historyMateriLogs = StudentMateriLog::with('materi:id,title,category,description')
            ->where('student_id', $student->id)
            ->orderByDesc('accessed_at')
            ->take(60)
            ->get();

        $competencyProfile = $competencyService->buildProfile($historyQuizResults, $historyMateriLogs);
        $weakCompetency = collect($competencyProfile['active_rows'] ?? [])
            ->sortBy(function ($row) {
                return (float) ($row['quiz_avg_score'] ?? 0);
            })
            ->first();

        $focusCompetencyKey = (string) ($weakCompetency['key'] ?? '');
        $focusCompetencyName = (string) ($weakCompetency['name'] ?? 'Umum');

        $orderedQuestions = $this->orderAdaptiveQuestions(
            $quiz->questions,
            $adaptiveProfile,
            $focusCompetencyKey
        );
        $quiz->setRelation('questions', $orderedQuestions->values());

        return view('siswa-quiz-detail', [
            'student' => $student,
            'quiz' => $quiz,
            'latestResult' => $latestResult,
            'questionHints' => session($this->quizHintSessionKey($quiz->id), []),
            'adaptiveProfile' => $adaptiveProfile,
            'focusCompetencyName' => $focusCompetencyName,
            'user' => auth()->user(),
        ]);
    }

    public function siswaQuizHint(Request $request, int $quizId, int $questionId, GeminiService $gemini)
    {
        $student = Student::where('email', auth()->user()->email)->firstOrFail();
        $quiz = Quiz::with('questions')->findOrFail($quizId);
        $question = $quiz->questions->firstWhere('id', $questionId);

        if (!$question) {
            return redirect()->route('siswa.quizzes.show', $quiz->id)->with('status', 'Soal tidak ditemukan untuk hint.');
        }

        $recentMateriReads = StudentMateriLog::with('materi:id,title,category,description')
            ->where('student_id', $student->id)
            ->orderByDesc('accessed_at')
            ->take(4)
            ->get();

        $materiContext = $recentMateriReads->map(function ($row) {
            $materi = $row->materi;
            if (!$materi) {
                return null;
            }

            return '- ' . $materi->title . ' [' . ($materi->category ?? 'Umum') . ']';
        })->filter()->implode("\n");

        $prompt = "Beri hint untuk siswa mengerjakan soal quiz berikut tanpa membocorkan jawaban final.\n"
            . 'Pertanyaan: ' . $question->question_text . "\n"
            . 'Pilihan: A. ' . $question->option_a . ' | B. ' . $question->option_b . ' | C. ' . $question->option_c . ' | D. ' . $question->option_d . "\n"
            . "Materi yang sudah dibaca siswa:\n" . ($materiContext !== '' ? $materiContext : '- Belum ada data') . "\n\n"
            . "Format jawaban:"
            . "\n- Konsep kunci: ..."
            . "\n- Cara eliminasi: ..."
            . "\n- Coba pikirkan: ..."
            . "\nMaksimal 90 kata.";

        $hint = $gemini->generateText(
            $prompt,
            'Kamu tutor quiz. Jangan sebutkan jawaban benar A/B/C/D secara eksplisit.'
        );

        if (!$hint) {
            return redirect()->route('siswa.quizzes.show', $quiz->id)->with('status', 'Hint belum bisa dibuat. Coba lagi.');
        }

        $hintMap = session($this->quizHintSessionKey($quiz->id), []);
        $hintMap[(string) $question->id] = trim($hint);
        session([$this->quizHintSessionKey($quiz->id) => $hintMap]);

        return redirect()->route('siswa.quizzes.show', $quiz->id)->with('status', 'Hint berhasil dibuat.');
    }

    public function siswaQuizSubmit(Request $request, int $id)
    {
        $student = Student::where('email', auth()->user()->email)->firstOrFail();
        $quiz = Quiz::with('questions')->findOrFail($id);

        $validated = $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $answers = $validated['answers'];
        $score = 0;

        foreach ($quiz->questions as $question) {
            $selected = strtoupper((string) ($answers[$question->id] ?? ''));
            if ($selected !== '' && $selected === strtoupper((string) $question->correct_answer)) {
                $score++;
            }
        }

        StudentQuizResult::create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'score' => $score,
        ]);

        session()->forget($this->quizHintSessionKey($quiz->id));

        return redirect()->route('siswa.quizzes.result', $quiz->id)
            ->with('status', 'Quiz berhasil dikirim.');
    }

    public function siswaQuizResult(int $id)
    {
        $student = Student::where('email', auth()->user()->email)->firstOrFail();
        $quiz = Quiz::with('teacher:id,name,email')->findOrFail($id);

        $result = StudentQuizResult::query()
            ->where('student_id', $student->id)
            ->where('quiz_id', $quiz->id)
            ->latest()
            ->firstOrFail();

        $totalQuestions = Quiz::withCount('questions')->findOrFail($id)->questions_count;
        $percent = $totalQuestions > 0 ? round(($result->score / $totalQuestions) * 100, 2) : 0;

        return view('siswa-quiz-result', [
            'student' => $student,
            'quiz' => $quiz,
            'result' => $result,
            'totalQuestions' => $totalQuestions,
            'percent' => $percent,
            'user' => auth()->user(),
        ]);
    }

    public function guru(Request $request)
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $teacher = Teacher::where('email', auth()->user()->email)->first();

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'trend' => ['nullable', 'in:meningkat,stabil,menurun'],
            'risk' => ['nullable', 'in:tinggi,sedang,rendah'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'sort' => ['nullable', 'in:name,avg_score,latest_score,materi_coverage,materi_accesses'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $filters = [
            'q' => trim((string) ($validated['q'] ?? '')),
            'trend' => (string) ($validated['trend'] ?? ''),
            'risk' => (string) ($validated['risk'] ?? ''),
            'from' => (string) ($validated['from'] ?? ''),
            'to' => (string) ($validated['to'] ?? ''),
            'sort' => (string) ($validated['sort'] ?? 'avg_score'),
            'direction' => (string) ($validated['direction'] ?? 'desc'),
            'per_page' => (int) ($validated['per_page'] ?? 10),
        ];

        $teacherMateriIds = collect();
        $quizResultsByStudent = collect();
        $materiLogsByStudent = collect();
        $latestTeacherQuizzes = collect();
        $latestTeacherMateris = collect();
        $recentStudentLearningActivities = collect();

        if ($teacher) {
            $teacherMateriIds = Materi::query()
                ->where('teacher_id', $teacher->id)
                ->pluck('id');

            $latestTeacherQuizzes = Quiz::query()
                ->where('teacher_id', $teacher->id)
                ->withCount('questions')
                ->latest()
                ->take(5)
                ->get(['id', 'title', 'created_at']);

            $latestTeacherMateris = Materi::query()
                ->where('teacher_id', $teacher->id)
                ->latest()
                ->take(5)
                ->get(['id', 'title', 'category', 'created_at']);

            $quizResultsByStudent = StudentQuizResult::with('quiz:id,teacher_id')
                ->whereHas('quiz', function ($query) use ($teacher) {
                    $query->where('teacher_id', $teacher->id);
                });

            if ($filters['from'] !== '') {
                $quizResultsByStudent->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
            }

            if ($filters['to'] !== '') {
                $quizResultsByStudent->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
            }

            $quizResultsByStudent = $quizResultsByStudent
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('student_id');

            if ($teacherMateriIds->isNotEmpty()) {
                $materiLogsByStudent = StudentMateriLog::query()
                    ->whereIn('materi_id', $teacherMateriIds);

                if ($filters['from'] !== '') {
                    $materiLogsByStudent->where('accessed_at', '>=', Carbon::parse($filters['from'])->startOfDay());
                }

                if ($filters['to'] !== '') {
                    $materiLogsByStudent->where('accessed_at', '<=', Carbon::parse($filters['to'])->endOfDay());
                }

                $materiLogsByStudent = $materiLogsByStudent
                    ->orderByDesc('accessed_at')
                    ->get()
                    ->groupBy('student_id');
            }

            $recentQuizAttempts = StudentQuizResult::query()
                ->with(['student:id,name,email', 'quiz:id,title,teacher_id'])
                ->whereHas('quiz', function ($query) use ($teacher) {
                    $query->where('teacher_id', $teacher->id);
                })
                ->latest()
                ->take(12)
                ->get()
                ->toBase()
                ->map(function (StudentQuizResult $row) {
                    return [
                        'type' => 'quiz',
                        'student_name' => $row->student->name ?? 'Siswa tidak diketahui',
                        'student_email' => $row->student->email ?? '-',
                        'title' => $row->quiz->title ?? 'Quiz tanpa judul',
                        'score' => (int) $row->score,
                        'activity_at' => $row->created_at,
                    ];
                });

            $recentMateriReads = StudentMateriLog::query()
                ->with(['student:id,name,email', 'materi:id,title,teacher_id'])
                ->whereHas('materi', function ($query) use ($teacher) {
                    $query->where('teacher_id', $teacher->id);
                })
                ->orderByDesc('accessed_at')
                ->take(12)
                ->get()
                ->toBase()
                ->map(function (StudentMateriLog $row) {
                    return [
                        'type' => 'materi',
                        'student_name' => $row->student->name ?? 'Siswa tidak diketahui',
                        'student_email' => $row->student->email ?? '-',
                        'title' => $row->materi->title ?? 'Materi tanpa judul',
                        'score' => null,
                        'activity_at' => $row->accessed_at,
                    ];
                });

            $recentStudentLearningActivities = $recentQuizAttempts
                ->merge($recentMateriReads)
                ->sortByDesc('activity_at')
                ->take(10)
                ->values();
        }

        $students = Student::orderBy('name')->get(['id', 'name', 'email']);
        $totalMateriGuru = $teacherMateriIds->count();

        $studentProgressRows = $students->map(function (Student $student) use ($quizResultsByStudent, $materiLogsByStudent, $totalMateriGuru) {
            $quizResults = collect($quizResultsByStudent->get($student->id, collect()));
            $materiLogs = collect($materiLogsByStudent->get($student->id, collect()));

            $totalAttempts = $quizResults->count();
            $averageScore = $totalAttempts > 0 ? round((float) $quizResults->avg('score'), 2) : 0.0;
            $latestScore = $totalAttempts > 0 ? (int) $quizResults->first()->score : 0;

            $latestThreeAvg = round((float) $quizResults->take(3)->avg('score'), 2);
            $previousThreeAvg = round((float) $quizResults->slice(3, 3)->avg('score'), 2);
            $trend = 'stabil';
            if ($totalAttempts >= 4 && $latestThreeAvg > $previousThreeAvg) {
                $trend = 'meningkat';
            } elseif ($totalAttempts >= 4 && $latestThreeAvg < $previousThreeAvg) {
                $trend = 'menurun';
            }

            $totalAccessLogs = $materiLogs->count();
            $uniqueMateriAccessed = $materiLogs->pluck('materi_id')->unique()->count();
            $coveragePercent = $totalMateriGuru > 0
                ? round(($uniqueMateriAccessed / $totalMateriGuru) * 100, 2)
                : 0.0;

            $riskLevel = $this->calculateEarlyWarningRisk(
                $averageScore,
                $latestScore,
                $coveragePercent,
                $trend,
                $totalAttempts
            );

            return [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'student_email' => $student->email,
                'quiz_total_attempts' => $totalAttempts,
                'quiz_average_score' => $averageScore,
                'quiz_latest_score' => $latestScore,
                'quiz_trend' => $trend,
                'materi_total_access_logs' => $totalAccessLogs,
                'materi_unique_accessed' => $uniqueMateriAccessed,
                'materi_coverage_percent' => $coveragePercent,
                'risk_level' => $riskLevel,
            ];
        });

        if ($filters['q'] !== '') {
            $query = mb_strtolower($filters['q'], 'UTF-8');
            $studentProgressRows = $studentProgressRows->filter(function (array $row) use ($query) {
                return str_contains(mb_strtolower($row['student_name'], 'UTF-8'), $query)
                    || str_contains(mb_strtolower($row['student_email'], 'UTF-8'), $query);
            });
        }

        if ($filters['trend'] !== '') {
            $studentProgressRows = $studentProgressRows->where('quiz_trend', $filters['trend']);
        }

        if ($filters['risk'] !== '') {
            $studentProgressRows = $studentProgressRows->where('risk_level', $filters['risk']);
        }

        $sortMap = [
            'name' => 'student_name',
            'avg_score' => 'quiz_average_score',
            'latest_score' => 'quiz_latest_score',
            'materi_coverage' => 'materi_coverage_percent',
            'materi_accesses' => 'materi_total_access_logs',
        ];
        $sortBy = $sortMap[$filters['sort']] ?? 'quiz_average_score';
        $direction = strtolower($filters['direction']) === 'asc' ? 'asc' : 'desc';

        $studentProgressRows = $direction === 'asc'
            ? $studentProgressRows->sortBy($sortBy)
            : $studentProgressRows->sortByDesc($sortBy);

        $studentProgressRows = $studentProgressRows->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = $filters['per_page'] > 0 ? $filters['per_page'] : 10;
        $totalRows = $studentProgressRows->count();

        $studentProgressRows = new LengthAwarePaginator(
            $studentProgressRows->forPage($page, $perPage)->values(),
            $totalRows,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $overallCoverage = round((float) $studentProgressRows->avg('materi_coverage_percent'), 2);
        $atRiskCount = $studentProgressRows->where('risk_level', 'tinggi')->count();
        $mediumRiskCount = $studentProgressRows->where('risk_level', 'sedang')->count();

        $stats = [
            'total_students' => Student::count(),
            'quiz_this_week' => Quiz::where('created_at', '>=', $startOfWeek)->count(),
            'analysis_this_week' => StudentProgressAnalysis::where('created_at', '>=', $startOfWeek)->count(),
            'average_score' => round((float) StudentQuizResult::avg('score'), 2),
            'total_materi_guru' => $totalMateriGuru,
            'overall_materi_coverage' => $overallCoverage,
            'at_risk_students' => $atRiskCount,
            'medium_risk_students' => $mediumRiskCount,
        ];

        $recentActivities = StudentProgressAnalysis::with(['student:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        return view('guru', [
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'latestTeacherQuizzes' => $latestTeacherQuizzes,
            'latestTeacherMateris' => $latestTeacherMateris,
            'recentStudentLearningActivities' => $recentStudentLearningActivities,
            'studentProgressRows' => $studentProgressRows,
            'filters' => $filters,
            'user' => auth()->user(),
        ]);
    }

    public function guruStudentsDestroy(int $id)
    {
        $teacher = Teacher::where('email', auth()->user()->email)->first();
        if (!$teacher) {
            return redirect()->route('guru.dashboard')->with('status', 'Akun guru tidak valid.');
        }

        $student = Student::findOrFail($id);
        $studentEmail = $student->email;
        $photoPath = $student->profile_photo_path;

        DB::transaction(function () use ($student, $studentEmail) {
            User::where('email', $studentEmail)->delete();
            $student->delete();
        });

        if ($photoPath) {
            Storage::disk('public')->delete($photoPath);
        }

        return redirect()->route('guru.dashboard')->with('status', 'Siswa berhasil dihapus.');
    }

    public function guruStudentsGenerateAi(Request $request, int $id, GeminiService $gemini, PhaseFWebCompetencyService $competencyService)
    {
        $teacher = Teacher::where('email', auth()->user()->email)->firstOrFail();
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'context' => ['nullable', 'string', 'max:500'],
        ]);

        $results = StudentQuizResult::with('quiz:id,title,teacher_id')
            ->where('student_id', $student->id)
            ->whereHas('quiz', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->orderByDesc('created_at')
            ->get();

        if ($results->isEmpty()) {
            return redirect()->route('guru.dashboard')->with('ai_feedback', [
                'student_id' => $student->id,
                'status' => 'error',
                'message' => 'Belum ada hasil quiz siswa untuk quiz milik Anda.',
                'history_id' => null,
            ]);
        }

        $totalQuiz = $results->count();
        $averageScore = round((float) $results->avg('score'), 2);
        $bestScore = (int) $results->max('score');
        $latestScore = (int) $results->first()->score;

        $recentSummary = $results->take(5)->values()->map(function (StudentQuizResult $item) {
            $quizTitle = $item->quiz->title ?? 'Quiz tanpa judul';
            return "- {$quizTitle}: skor {$item->score} ({$item->created_at->format('Y-m-d H:i')})";
        })->implode("\n");

        $teacherMateriIds = Materi::query()
            ->where('teacher_id', $teacher->id)
            ->pluck('id');

        $materiLogs = StudentMateriLog::with('materi:id,title,category,description,teacher_id')
            ->where('student_id', $student->id)
            ->whereIn('materi_id', $teacherMateriIds)
            ->orderByDesc('accessed_at')
            ->get();

        $competencyProfile = $competencyService->buildProfile($results, $materiLogs);

        $teacherContext = trim((string) ($validated['context'] ?? ''));

        $prompt = "Berikut data perkembangan siswa:\n"
            . "Nama siswa: {$student->name}\n"
            . "Email siswa: {$student->email}\n"
            . "Total quiz dikerjakan: {$totalQuiz}\n"
            . "Rata-rata skor: {$averageScore}\n"
            . "Skor terbaik: {$bestScore}\n"
            . "Skor terbaru: {$latestScore}\n"
            . "Ringkasan 5 quiz terakhir:\n{$recentSummary}\n\n"
            . "Peta keterhubungan soal dan materi berdasarkan CP/ATP Pemrograman Website Fase F:\n"
            . $competencyProfile['context_text'] . "\n\n"
            . ($teacherContext !== '' ? "Konteks dari guru: {$teacherContext}\n\n" : '')
            . "Tolong buat deskripsi perkembangan belajar siswa dalam Bahasa Indonesia dengan format WAJIB persis ini:\n"
            . "Ringkasan: <isi>\n"
            . "Kekuatan: <isi>\n"
            . "Perlu Ditingkatkan: <isi>\n"
            . "Tindak Lanjut: <isi>\n"
            . "Setiap bagian 1-2 kalimat, jelas, spesifik, suportif, dan menautkan evaluasi ke CP/ATP yang relevan. Total maksimal 220 kata.";

        $analysis = $gemini->generateText(
            $prompt,
            'Kamu adalah asisten pendidikan. Berikan evaluasi perkembangan belajar yang konstruktif, empatik, dan dapat ditindaklanjuti.'
        );

        if (!$analysis) {
            return redirect()->route('guru.dashboard')->with('ai_feedback', [
                'student_id' => $student->id,
                'status' => 'error',
                'message' => 'Analisis AI gagal dibuat. Periksa konfigurasi GEMINI_API_KEY.',
                'history_id' => null,
            ]);
        }

        $savedAnalysis = StudentProgressAnalysis::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'total_quiz' => $totalQuiz,
            'average_score' => $averageScore,
            'best_score' => $bestScore,
            'latest_score' => $latestScore,
            'teacher_context' => $teacherContext !== '' ? $teacherContext : null,
            'analysis' => $analysis,
        ]);

        return redirect()->route('guru.dashboard')->with('ai_feedback', [
            'student_id' => $student->id,
            'status' => 'success',
            'message' => 'Analisis AI berhasil dibuat untuk siswa ' . $student->name . '.',
            'history_id' => $savedAnalysis->id,
        ]);
    }

    public function guruStudentProgress(Request $request, int $id)
    {
        $teacher = Teacher::where('email', auth()->user()->email)->firstOrFail();
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'weeks' => ['nullable', 'integer', 'min:4', 'max:16'],
        ]);

        $weeks = (int) ($validated['weeks'] ?? 8);
        $data = $this->collectGuruStudentProgress($teacher, $student, $weeks);

        $interventions = StudentInterventionPlan::query()
            ->where('teacher_id', $teacher->id)
            ->where('student_id', $student->id)
            ->orderByRaw("CASE status WHEN 'berjalan' THEN 0 WHEN 'belum_mulai' THEN 1 ELSE 2 END")
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->get();

        return view('guru-student-progress', [
            'student' => $student,
            'weeks' => $weeks,
            'weeklyProgress' => $data['weeklyProgress'],
            'maxQuizAvg' => $data['maxQuizAvg'],
            'maxMateriAccess' => $data['maxMateriAccess'],
            'categorySummary' => $data['categorySummary'],
            'competencySummary' => $data['competencySummary'],
            'summary' => $data['summary'],
            'interventions' => $interventions,
            'user' => auth()->user(),
        ]);
    }

    public function guruStudentInterventionsStore(Request $request, int $id)
    {
        $teacher = Teacher::where('email', auth()->user()->email)->firstOrFail();
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'risk_level' => ['required', 'in:tinggi,sedang,rendah'],
            'action_items' => ['nullable', 'string', 'max:3000'],
            'target_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:belum_mulai,berjalan,selesai'],
            'notes' => ['nullable', 'string', 'max:1500'],
        ]);

        StudentInterventionPlan::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'title' => trim((string) $validated['title']),
            'risk_level' => (string) $validated['risk_level'],
            'action_items' => $validated['action_items'] ?? null,
            'target_score' => $validated['target_score'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'status' => (string) ($validated['status'] ?? 'belum_mulai'),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('guru.students.progress', $student->id)->with('status', 'Rencana tindak lanjut berhasil ditambahkan.');
    }

    public function guruStudentInterventionsUpdate(Request $request, int $id)
    {
        $teacher = Teacher::where('email', auth()->user()->email)->firstOrFail();

        $plan = StudentInterventionPlan::query()
            ->where('teacher_id', $teacher->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'risk_level' => ['required', 'in:tinggi,sedang,rendah'],
            'action_items' => ['nullable', 'string', 'max:3000'],
            'target_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', 'in:belum_mulai,berjalan,selesai'],
            'notes' => ['nullable', 'string', 'max:1500'],
        ]);

        $plan->update([
            'title' => trim((string) $validated['title']),
            'risk_level' => (string) $validated['risk_level'],
            'action_items' => $validated['action_items'] ?? null,
            'target_score' => $validated['target_score'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'status' => (string) $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('guru.students.progress', $plan->student_id)->with('status', 'Rencana tindak lanjut berhasil diperbarui.');
    }

    public function guruStudentInterventionsDestroy(int $id)
    {
        $teacher = Teacher::where('email', auth()->user()->email)->firstOrFail();

        $plan = StudentInterventionPlan::query()
            ->where('teacher_id', $teacher->id)
            ->findOrFail($id);

        $studentId = (int) $plan->student_id;
        $plan->delete();

        return redirect()->route('guru.students.progress', $studentId)->with('status', 'Rencana tindak lanjut dihapus.');
    }

    public function guruStudentProgressPdf(Request $request, int $id)
    {
        $teacher = Teacher::where('email', auth()->user()->email)->firstOrFail();
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'weeks' => ['nullable', 'integer', 'min:4', 'max:16'],
        ]);

        $weeks = (int) ($validated['weeks'] ?? 8);
        $data = $this->collectGuruStudentProgress($teacher, $student, $weeks);

        $pdf = Pdf::loadView('pdf.guru-student-progress', [
            'student' => $student,
            'weeks' => $weeks,
            'weeklyProgress' => $data['weeklyProgress'],
            'categorySummary' => $data['categorySummary'],
            'summary' => $data['summary'],
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('progres-' . $student->id . '-guru-' . now()->format('YmdHis') . '.pdf');
    }

    private function collectGuruStudentProgress(Teacher $teacher, Student $student, int $weeks): array
    {
        $start = Carbon::now()->startOfWeek()->subWeeks($weeks - 1);
        $end = Carbon::now()->endOfWeek();

        $teacherMateriIds = Materi::query()
            ->where('teacher_id', $teacher->id)
            ->pluck('id');

        $quizRows = StudentQuizResult::query()
            ->where('student_id', $student->id)
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('quiz', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->orderBy('created_at')
            ->get(['score', 'created_at']);

        $materiRows = StudentMateriLog::with('materi:id,category')
            ->where('student_id', $student->id)
            ->whereIn('materi_id', $teacherMateriIds)
            ->whereBetween('accessed_at', [$start, $end])
            ->orderBy('accessed_at')
            ->get(['materi_id', 'accessed_at']);

        $quizRowsForCompetency = StudentQuizResult::query()
            ->with('quiz:id,title,description,teacher_id')
            ->where('student_id', $student->id)
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('quiz', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->orderByDesc('created_at')
            ->get();

        $materiRowsForCompetency = StudentMateriLog::query()
            ->with('materi:id,title,category,description,teacher_id')
            ->where('student_id', $student->id)
            ->whereIn('materi_id', $teacherMateriIds)
            ->whereBetween('accessed_at', [$start, $end])
            ->orderByDesc('accessed_at')
            ->get();

        $competencyService = app(PhaseFWebCompetencyService::class);
        $competencyProfile = $competencyService->buildProfile($quizRowsForCompetency, $materiRowsForCompetency);

        $competencySummary = collect($competencyProfile['rows'] ?? [])->map(function ($row) {
            $avg = (float) ($row['quiz_avg_score'] ?? 0);
            $accesses = (int) ($row['materi_accesses'] ?? 0);

            $mastery = min(100, (int) round(($avg * 10 * 0.75) + min(25, $accesses * 5)));
            $status = 'perlu_penguatan';
            if ($mastery >= 75) {
                $status = 'kuat';
            } elseif ($mastery >= 45) {
                $status = 'cukup';
            }

            return [
                'key' => $row['key'] ?? '',
                'name' => $row['name'] ?? 'Kompetensi',
                'quiz_attempts' => (int) ($row['quiz_attempts'] ?? 0),
                'quiz_avg_score' => $avg,
                'materi_accesses' => $accesses,
                'mastery_score' => $mastery,
                'status' => $status,
            ];
        })->sortBy('mastery_score')->values();

        $weekMap = collect();
        for ($i = 0; $i < $weeks; $i++) {
            $weekStart = $start->copy()->addWeeks($i)->startOfWeek();
            $key = $weekStart->format('o-W');
            $weekMap->put($key, [
                'week_key' => $key,
                'week_label' => $weekStart->translatedFormat('d M') . ' - ' . $weekStart->copy()->endOfWeek()->translatedFormat('d M'),
                'quiz_avg' => 0.0,
                'quiz_attempts' => 0,
                'materi_accesses' => 0,
            ]);
        }

        $quizGrouped = $quizRows->groupBy(function ($item) {
            return Carbon::parse($item->created_at)->startOfWeek()->format('o-W');
        });

        foreach ($quizGrouped as $key => $rows) {
            if ($weekMap->has($key)) {
                $entry = $weekMap->get($key);
                $entry['quiz_attempts'] = $rows->count();
                $entry['quiz_avg'] = round((float) $rows->avg('score'), 2);
                $weekMap->put($key, $entry);
            }
        }

        $materiGrouped = $materiRows->groupBy(function ($item) {
            return Carbon::parse($item->accessed_at)->startOfWeek()->format('o-W');
        });

        foreach ($materiGrouped as $key => $rows) {
            if ($weekMap->has($key)) {
                $entry = $weekMap->get($key);
                $entry['materi_accesses'] = $rows->count();
                $weekMap->put($key, $entry);
            }
        }

        $weeklyProgress = $weekMap->values();
        $maxQuizAvg = max(1, (float) $weeklyProgress->max('quiz_avg'));
        $maxMateriAccess = max(1, (int) $weeklyProgress->max('materi_accesses'));

        $categorySummary = $materiRows
            ->groupBy(function ($item) {
                return $item->materi->category ?? 'Tanpa Kategori';
            })
            ->map(function ($rows, $category) {
                return [
                    'category' => $category,
                    'access_count' => $rows->count(),
                    'unique_materi' => $rows->pluck('materi_id')->unique()->count(),
                ];
            })
            ->sortByDesc('access_count')
            ->values();

        $totalAttempts = $quizRows->count();
        $averageScore = $totalAttempts > 0 ? round((float) $quizRows->avg('score'), 2) : 0.0;
        $latestScore = $totalAttempts > 0 ? (int) $quizRows->last()->score : 0;
        $totalMateriGuru = $teacherMateriIds->count();
        $uniqueMateriAccessed = $materiRows->pluck('materi_id')->unique()->count();
        $coveragePercent = $totalMateriGuru > 0 ? round(($uniqueMateriAccessed / $totalMateriGuru) * 100, 2) : 0.0;

        return [
            'weeklyProgress' => $weeklyProgress,
            'maxQuizAvg' => $maxQuizAvg,
            'maxMateriAccess' => $maxMateriAccess,
            'categorySummary' => $categorySummary,
            'competencySummary' => $competencySummary,
            'summary' => [
                'total_attempts' => $totalAttempts,
                'average_score' => $averageScore,
                'latest_score' => $latestScore,
                'materi_coverage' => $coveragePercent,
            ],
        ];
    }

    public function admin()
    {
        $now = Carbon::now();
        $startOfDay = $now->copy()->startOfDay();

        $stats = [
            'total_teachers' => Teacher::count(),
            'total_students' => Student::count(),
            'ai_requests_today' => StudentProgressAnalysis::where('created_at', '>=', $startOfDay)->count(),
            'api_error_rate' => 0.8,
        ];

        $teacherRows = Teacher::query()
            ->leftJoin('quizzes', 'quizzes.teacher_id', '=', 'teachers.id')
            ->leftJoin('student_progress_analyses', 'student_progress_analyses.teacher_id', '=', 'teachers.id')
            ->groupBy('teachers.id', 'teachers.name')
            ->select([
                'teachers.name',
                DB::raw('COUNT(DISTINCT quizzes.id) as quiz_count'),
                DB::raw('COUNT(DISTINCT student_progress_analyses.id) as analysis_count'),
            ])
            ->orderByDesc('analysis_count')
            ->limit(6)
            ->get();

        return view('admin', [
            'stats' => $stats,
            'teacherRows' => $teacherRows,
            'uptime' => 97,
            'user' => auth()->user(),
        ]);
    }

    public function guruHistory(Request $request)
    {
        $teacher = Teacher::where('email', auth()->user()->email)->first();

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
        ]);

        $query = StudentProgressAnalysis::with(['student:id,name,email'])
            ->orderByDesc('created_at');

        if ($teacher) {
            $query->where('teacher_id', $teacher->id);
        }

        if (!empty($validated['from'])) {
            $query->where('created_at', '>=', Carbon::parse($validated['from'])->startOfDay());
        }

        if (!empty($validated['to'])) {
            $query->where('created_at', '<=', Carbon::parse($validated['to'])->endOfDay());
        }

        if (!empty($validated['student_id'])) {
            $query->where('student_id', $validated['student_id']);
        }

        $history = $query->paginate(10)->withQueryString();
        $students = Student::orderBy('name')->get(['id', 'name']);

        return view('guru-history', [
            'history' => $history,
            'students' => $students,
            'filters' => $validated,
            'user' => auth()->user(),
        ]);
    }

    public function guruHistoryDetail(int $id)
    {
        $teacher = Teacher::where('email', auth()->user()->email)->first();

        $query = StudentProgressAnalysis::with(['student:id,name,email'])
            ->where('id', $id);

        if ($teacher) {
            $query->where('teacher_id', $teacher->id);
        }

        $analysis = $query->firstOrFail();

        $analysisSections = $this->parseAnalysisSections($analysis->analysis);
        $qualityBadge = $this->calculateQualityBadge($analysisSections);

        return view('guru-history-detail', [
            'analysis' => $analysis,
            'analysisSections' => $analysisSections,
            'qualityBadge' => $qualityBadge,
            'user' => auth()->user(),
        ]);
    }

    public function guruEarlyWarningPdf(Request $request)
    {
        $teacher = Teacher::where('email', auth()->user()->email)->firstOrFail();

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'trend' => ['nullable', 'in:meningkat,stabil,menurun'],
            'risk' => ['nullable', 'in:tinggi,sedang,rendah'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $filters = [
            'q' => trim((string) ($validated['q'] ?? '')),
            'trend' => (string) ($validated['trend'] ?? ''),
            'risk' => (string) ($validated['risk'] ?? ''),
            'from' => (string) ($validated['from'] ?? ''),
            'to' => (string) ($validated['to'] ?? ''),
        ];

        $teacherMateriIds = Materi::query()
            ->where('teacher_id', $teacher->id)
            ->pluck('id');

        $quizResultsByStudent = StudentQuizResult::with('quiz:id,teacher_id')
            ->whereHas('quiz', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            });

        if ($filters['from'] !== '') {
            $quizResultsByStudent->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }

        if ($filters['to'] !== '') {
            $quizResultsByStudent->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        $quizResultsByStudent = $quizResultsByStudent
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('student_id');

        $materiLogsByStudent = collect();
        if ($teacherMateriIds->isNotEmpty()) {
            $materiLogsByStudent = StudentMateriLog::query()->whereIn('materi_id', $teacherMateriIds);

            if ($filters['from'] !== '') {
                $materiLogsByStudent->where('accessed_at', '>=', Carbon::parse($filters['from'])->startOfDay());
            }

            if ($filters['to'] !== '') {
                $materiLogsByStudent->where('accessed_at', '<=', Carbon::parse($filters['to'])->endOfDay());
            }

            $materiLogsByStudent = $materiLogsByStudent
                ->orderByDesc('accessed_at')
                ->get()
                ->groupBy('student_id');
        }

        $students = Student::orderBy('name')->get(['id', 'name', 'email']);
        $totalMateriGuru = $teacherMateriIds->count();

        $rows = $students->map(function (Student $student) use ($quizResultsByStudent, $materiLogsByStudent, $totalMateriGuru) {
            $quizResults = collect($quizResultsByStudent->get($student->id, collect()));
            $materiLogs = collect($materiLogsByStudent->get($student->id, collect()));

            $totalAttempts = $quizResults->count();
            $averageScore = $totalAttempts > 0 ? round((float) $quizResults->avg('score'), 2) : 0.0;
            $latestScore = $totalAttempts > 0 ? (int) $quizResults->first()->score : 0;

            $latestThreeAvg = round((float) $quizResults->take(3)->avg('score'), 2);
            $previousThreeAvg = round((float) $quizResults->slice(3, 3)->avg('score'), 2);
            $trend = 'stabil';
            if ($totalAttempts >= 4 && $latestThreeAvg > $previousThreeAvg) {
                $trend = 'meningkat';
            } elseif ($totalAttempts >= 4 && $latestThreeAvg < $previousThreeAvg) {
                $trend = 'menurun';
            }

            $totalAccessLogs = $materiLogs->count();
            $coveragePercent = $totalMateriGuru > 0
                ? round(($materiLogs->pluck('materi_id')->unique()->count() / $totalMateriGuru) * 100, 2)
                : 0.0;

            $riskLevel = $this->calculateEarlyWarningRisk(
                $averageScore,
                $latestScore,
                $coveragePercent,
                $trend,
                $totalAttempts
            );

            return [
                'student_name' => $student->name,
                'student_email' => $student->email,
                'quiz_total_attempts' => $totalAttempts,
                'quiz_average_score' => $averageScore,
                'quiz_latest_score' => $latestScore,
                'quiz_trend' => $trend,
                'materi_total_access_logs' => $totalAccessLogs,
                'materi_coverage_percent' => $coveragePercent,
                'risk_level' => $riskLevel,
                'recommendation' => $this->buildEarlyWarningAction($riskLevel, $trend, $coveragePercent),
            ];
        });

        if ($filters['q'] !== '') {
            $query = mb_strtolower($filters['q'], 'UTF-8');
            $rows = $rows->filter(function (array $row) use ($query) {
                return str_contains(mb_strtolower($row['student_name'], 'UTF-8'), $query)
                    || str_contains(mb_strtolower($row['student_email'], 'UTF-8'), $query);
            });
        }

        if ($filters['trend'] !== '') {
            $rows = $rows->where('quiz_trend', $filters['trend']);
        }

        if ($filters['risk'] !== '') {
            $rows = $rows->where('risk_level', $filters['risk']);
        }

        $rows = $rows->sortBy(function ($row) {
            $weight = ['tinggi' => 0, 'sedang' => 1, 'rendah' => 2];
            return ($weight[$row['risk_level']] ?? 3) . '-' . str_pad((string) round($row['quiz_average_score'], 2), 6, '0', STR_PAD_LEFT);
        })->values();

        $summary = [
            'total_students' => $rows->count(),
            'risk_tinggi' => $rows->where('risk_level', 'tinggi')->count(),
            'risk_sedang' => $rows->where('risk_level', 'sedang')->count(),
            'risk_rendah' => $rows->where('risk_level', 'rendah')->count(),
        ];

        $pdf = Pdf::loadView('pdf.guru-early-warning', [
            'teacher' => $teacher,
            'rows' => $rows,
            'summary' => $summary,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('early-warning-guru-' . $teacher->id . '-' . now()->format('YmdHis') . '.pdf');
    }

    public function adminUsers(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $usersQuery = User::query()->orderByDesc('created_at');
        if ($search !== '') {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
            });
        }

        $users = $usersQuery->paginate(10)->withQueryString();

        return view('admin-users', [
            'users' => $users,
            'search' => $search,
            'user' => auth()->user(),
        ]);
    }

    public function adminGuruSiswa(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $teachersQuery = Teacher::query()
            ->withCount(['quizzes', 'materis', 'progressAnalyses'])
            ->orderBy('name');

        if ($search !== '') {
            $teachersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $teachers = $teachersQuery->paginate(10, ['*'], 'teachers_page')->withQueryString();

        $studentQuizCounts = StudentQuizResult::query()
            ->selectRaw('student_id, COUNT(*) as total_quiz_attempts, AVG(score) as avg_score')
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        $studentMateriCounts = StudentMateriLog::query()
            ->selectRaw('student_id, COUNT(*) as total_materi_accesses')
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        $studentsQuery = Student::query()->orderBy('name');
        if ($search !== '') {
            $studentsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $studentsQuery->paginate(10, ['*'], 'students_page')->withQueryString();

        $students->getCollection()->transform(function (Student $student) use ($studentQuizCounts, $studentMateriCounts) {
            $quizStat = $studentQuizCounts->get($student->id);
            $materiStat = $studentMateriCounts->get($student->id);

            $student->total_quiz_attempts = (int) ($quizStat->total_quiz_attempts ?? 0);
            $student->avg_quiz_score = round((float) ($quizStat->avg_score ?? 0), 2);
            $student->total_materi_accesses = (int) ($materiStat->total_materi_accesses ?? 0);

            return $student;
        });

        return view('admin-guru-siswa', [
            'teachers' => $teachers,
            'students' => $students,
            'search' => $search,
            'user' => auth()->user(),
        ]);
    }

    public function adminUsersStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:admin,guru,siswa'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return redirect()->route('admin.users')->with('status', 'User berhasil ditambahkan.');
    }

    public function adminUsersUpdate(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'role' => ['required', 'in:admin,guru,siswa'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'role' => $validated['role'],
        ]);

        return redirect()->route('admin.users')->with('status', 'User berhasil diperbarui.');
    }

    public function adminUsersResetPassword(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'new_password' => ['required', 'string', 'min:8'],
        ]);

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return redirect()->route('admin.users')->with('status', 'Password user berhasil direset.');
    }

    public function adminUsersDestroy(int $id)
    {
        $user = User::findOrFail($id);

        if (auth()->id() === $user->id) {
            return redirect()->route('admin.users')->with('status', 'Akun aktif tidak bisa dihapus sendiri.');
        }

        $user->delete();

        return redirect()->route('admin.users')->with('status', 'User berhasil dihapus.');
    }

    private function parseAnalysisSections(string $analysis): array
    {
        $sections = [
            'ringkasan' => '',
            'kekuatan' => '',
            'perlu_ditingkatkan' => '',
            'tindak_lanjut' => '',
        ];

        $lines = preg_split('/\R/u', trim($analysis)) ?: [];
        $current = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^(?:\d+[\)\.]?\s*)?(?:\*\*)?Ringkasan(?:\s+Perkembangan)?(?:\*\*)?\s*:\s*(.*)$/iu', $line, $m)) {
                $current = 'ringkasan';
                $sections[$current] .= trim((string) ($m[1] ?? ''));
                continue;
            }

            if (preg_match('/^(?:\d+[\)\.]?\s*)?(?:\*\*)?Kekuatan(?:\s+Siswa)?(?:\*\*)?\s*:\s*(.*)$/iu', $line, $m)) {
                $current = 'kekuatan';
                $sections[$current] .= trim((string) ($m[1] ?? ''));
                continue;
            }

            if (preg_match('/^(?:\d+[\)\.]?\s*)?(?:\*\*)?(?:Perlu\s+Ditingkatkan|Area\s+yang\s+Perlu\s+Ditingkatkan)(?:\*\*)?\s*:\s*(.*)$/iu', $line, $m)) {
                $current = 'perlu_ditingkatkan';
                $sections[$current] .= trim((string) ($m[1] ?? ''));
                continue;
            }

            if (preg_match('/^(?:\d+[\)\.]?\s*)?(?:\*\*)?(?:Tindak\s+Lanjut|Saran\s+Tindak\s+Lanjut)(?:\*\*)?\s*:\s*(.*)$/iu', $line, $m)) {
                $current = 'tindak_lanjut';
                $sections[$current] .= trim((string) ($m[1] ?? ''));
                continue;
            }

            if ($current) {
                $sections[$current] .= ($sections[$current] !== '' ? ' ' : '') . $line;
            }
        }

        if (implode('', $sections) === '') {
            $sections['ringkasan'] = trim($analysis);
        }

        return $sections;
    }

    private function calculateQualityBadge(array $sections): array
    {
        $perluDitingkatkan = mb_strtolower((string) ($sections['perlu_ditingkatkan'] ?? ''), 'UTF-8');
        $tindakLanjut = mb_strtolower((string) ($sections['tindak_lanjut'] ?? ''), 'UTF-8');
        $combined = $perluDitingkatkan . ' ' . $tindakLanjut;

        $highRiskKeywords = [
            'tertinggal',
            'perlu perhatian',
            'belum paham',
            'kesulitan',
            'rendah',
            'belum konsisten',
            'sering salah',
            'pendampingan intensif',
            'remedial',
        ];

        $mediumRiskKeywords = [
            'perlu ditingkatkan',
            'cukup',
            'latihan',
            'penguatan',
            'masih perlu',
            'belum optimal',
            'konsistensi',
            'review',
        ];

        $score = 0;
        foreach ($highRiskKeywords as $keyword) {
            if (str_contains($combined, $keyword)) {
                $score += 2;
            }
        }

        foreach ($mediumRiskKeywords as $keyword) {
            if (str_contains($combined, $keyword)) {
                $score += 1;
            }
        }

        if ($score >= 4) {
            return [
                'key' => 'perlu_perhatian',
                'label' => 'Perlu Perhatian',
                'color' => '#dc2626',
                'bg_color' => '#fee2e2',
            ];
        }

        if ($score >= 1) {
            return [
                'key' => 'cukup',
                'label' => 'Cukup',
                'color' => '#b45309',
                'bg_color' => '#fef3c7',
            ];
        }

        return [
            'key' => 'baik',
            'label' => 'Baik',
            'color' => '#166534',
            'bg_color' => '#dcfce7',
        ];
    }

    private function calculateAdaptiveProfile(int $latestScore, float $averageScore): array
    {
        $anchor = $latestScore > 0 ? $latestScore : (int) round($averageScore);

        if ($anchor >= 80) {
            return [
                'level' => 'lanjut',
                'label' => 'Tantangan Lanjut',
                'message' => 'Gunakan soal analisis dan proyek mini agar progres tetap naik.',
            ];
        }

        if ($anchor >= 60) {
            return [
                'level' => 'menengah',
                'label' => 'Penguatan Menengah',
                'message' => 'Fokus di latihan bertahap dan cek konsep inti tiap topik.',
            ];
        }

        return [
            'level' => 'dasar',
            'label' => 'Remedial Dasar',
            'message' => 'Mulai dari konsep fondasi dan latihan singkat berulang.',
        ];
    }

    private function buildLearningPathPlan(array $adaptiveProfile, array $competencyProfile, $recentQuizResults, $recentMateriAccess): array
    {
        $focusRows = collect($competencyProfile['rows'] ?? [])->sortBy(function ($row) {
            $attempts = (int) ($row['quiz_attempts'] ?? 0);
            $avg = (float) ($row['quiz_avg_score'] ?? 0);
            return ($attempts > 0 ? 0 : 1) + $avg;
        })->take(2)->values();

        $focusNames = $focusRows->pluck('name')->filter()->values();
        $focusText = $focusNames->isNotEmpty()
            ? $focusNames->implode(' + ')
            : 'HTML semantik + CSS responsif';

        return [
            ['day' => 'Hari 1', 'task' => 'Review konsep inti: ' . $focusText],
            ['day' => 'Hari 2', 'task' => 'Ringkas 3 poin penting dari materi yang dibaca terakhir'],
            ['day' => 'Hari 3', 'task' => 'Kerjakan 1 quiz level ' . $adaptiveProfile['level'] . ' lalu evaluasi salah benar'],
            ['day' => 'Hari 4', 'task' => 'Ulangi topik tersulit dan buat 5 catatan rumus/aturan'],
            ['day' => 'Hari 5', 'task' => 'Latihan soal konsep campuran (HTML, CSS, JS/API)'],
            ['day' => 'Hari 6', 'task' => 'Diskusi dengan chatbot materi tentang kebingungan utama'],
            ['day' => 'Hari 7', 'task' => 'Kerjakan quiz ulang dan bandingkan dengan skor sebelumnya'],
        ];
    }

    private function calculateEarlyWarningRisk(float $avgScore, int $latestScore, float $coveragePercent, string $trend, int $attempts): string
    {
        $score = 0;
        if ($attempts >= 2 && $avgScore < 60) {
            $score += 2;
        } elseif ($avgScore < 75) {
            $score += 1;
        }

        if ($latestScore > 0 && $latestScore < 60) {
            $score += 2;
        }

        if ($coveragePercent < 35) {
            $score += 2;
        } elseif ($coveragePercent < 60) {
            $score += 1;
        }

        if ($trend === 'menurun') {
            $score += 2;
        }

        if ($score >= 5) {
            return 'tinggi';
        }

        if ($score >= 3) {
            return 'sedang';
        }

        return 'rendah';
    }

    private function buildEarlyWarningAction(string $riskLevel, string $trend, float $coveragePercent): string
    {
        if ($riskLevel === 'tinggi') {
            return 'Lakukan pendampingan intensif 1:1, berikan remedial terstruktur, dan wajibkan ulang materi inti sebelum quiz berikutnya.';
        }

        if ($riskLevel === 'sedang') {
            return 'Lakukan penguatan terarah pada topik lemah, pantau latihan harian singkat, dan cek progres di akhir pekan.';
        }

        if ($trend === 'meningkat' && $coveragePercent >= 70) {
            return 'Pertahankan ritme belajar dan naikkan tantangan bertahap melalui soal analisis lanjutan.';
        }

        return 'Jaga konsistensi belajar, review topik yang belum stabil, dan lanjutkan latihan rutin ringan.';
    }

    private function quizHintSessionKey(int $quizId): string
    {
        return 'siswa_quiz_hint_' . $quizId;
    }

    private function buildDashboardCitationItemsFromRecentAccesses(array|Collection $recentAccesses): array
    {
        return collect($recentAccesses)->take(3)->map(function ($item) {
            $title = (string) ($item['title'] ?? '');
            if ($title === '') {
                return null;
            }

            $materi = Materi::query()->where('title', $title)->first(['id', 'title', 'category']);
            if (!$materi) {
                return null;
            }

            return [
                'title' => $materi->title,
                'category' => $materi->category ?? 'Umum',
                'url' => route('siswa.materis.show', $materi->id),
            ];
        })->filter()->values()->all();
    }

    private function buildCitationItemsFromMateris(Collection $materis): array
    {
        return $materis->map(function ($item) {
            return [
                'title' => $item->title ?? 'Materi',
                'category' => $item->category ?? 'Umum',
                'url' => route('siswa.materis.show', $item->id),
            ];
        })->take(3)->values()->all();
    }

    private function orderAdaptiveQuestions(Collection $questions, array $adaptiveProfile, string $focusCompetencyKey = ''): Collection
    {
        $targetDifficulty = match ((string) ($adaptiveProfile['level'] ?? 'menengah')) {
            'pemulihan' => 'dasar',
            'akselerasi' => 'lanjut',
            default => 'menengah',
        };

        return $questions->sortBy(function ($question) use ($targetDifficulty, $focusCompetencyKey) {
            $difficulty = (string) ($question->difficulty_level ?? 'menengah');
            $competency = (string) ($question->competency_key ?? '');

            $difficultyWeight = $difficulty === $targetDifficulty ? 0 : 1;
            $competencyWeight = ($focusCompetencyKey !== '' && $competency === $focusCompetencyKey) ? 0 : 1;

            return $difficultyWeight . '-' . $competencyWeight . '-' . str_pad((string) $question->id, 10, '0', STR_PAD_LEFT);
        })->values();
    }

    private function materiChatSessionKey(int $materiId): string
    {
        return 'siswa_materi_chat_history_' . $materiId;
    }
}
