<?php

namespace App\Http\Controllers;

use App\Models\Materi;
use App\Models\Student;
use App\Models\StudentMateriLog;
use App\Models\StudentProgressAnalysis;
use App\Models\StudentQuizResult;
use App\Models\Teacher;
use App\Services\GeminiService;
use App\Services\PhaseFWebCompetencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StudentProgressController extends Controller
{
    public function overview(Request $request, int $studentId)
    {
        $teacher = $request->user();
        if (!$teacher instanceof Teacher) {
            return response()->json([
                'ok' => false,
                'message' => 'Akses ditolak (khusus guru).',
            ], 403);
        }
        $student = Student::find($studentId);
        if (!$student) {
            return response()->json([
                'ok' => false,
                'message' => 'Siswa tidak ditemukan.',
            ], 404);
        }

        $results = StudentQuizResult::with('quiz:id,title,teacher_id')
            ->where('student_id', $student->id)
            ->whereHas('quiz', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->orderByDesc('created_at')
            ->get();

        $totalAttempts = $results->count();
        $averageScore = $totalAttempts > 0 ? round((float) $results->avg('score'), 2) : 0.0;
        $bestScore = $totalAttempts > 0 ? (int) $results->max('score') : 0;
        $latestScore = $totalAttempts > 0 ? (int) $results->first()->score : 0;

        $recentAttempts = $results->take(5)->values()->map(function ($item) {
            return [
                'quiz_id' => $item->quiz_id,
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

        $teacherMateriIds = Materi::query()
            ->where('teacher_id', $teacher->id)
            ->pluck('id');

        $totalMateri = $teacherMateriIds->count();

        $recentMateri = Materi::query()
            ->whereIn('id', $teacherMateriIds)
            ->orderByDesc('created_at')
            ->take(5)
            ->get(['id', 'title', 'category', 'created_at']);

        $materiLogs = StudentMateriLog::with('materi:id,title,category,teacher_id')
            ->where('student_id', $student->id)
            ->whereIn('materi_id', $teacherMateriIds)
            ->orderByDesc('accessed_at')
            ->get();

        $totalAccessLogs = $materiLogs->count();
        $viewedMateriIds = $materiLogs->pluck('materi_id')->unique();
        $uniqueMateriAccessed = $viewedMateriIds->count();
        $coveragePercent = $totalMateri > 0
            ? round(($uniqueMateriAccessed / $totalMateri) * 100, 2)
            : 0.0;

        $lastAccessAt = $materiLogs->first()?->accessed_at;

        $recentMateriAccesses = $materiLogs->take(10)->map(function ($item) {
            return [
                'materi_id' => $item->materi_id,
                'title' => $item->materi->title ?? 'Materi tanpa judul',
                'category' => $item->materi->category,
                'accessed_at' => $item->accessed_at,
            ];
        })->values();

        $categoryAccessSummary = $materiLogs
            ->map(function ($item) {
                return $item->materi->category ?? 'Tanpa Kategori';
            })
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->map(function ($count, $category) {
                return [
                    'category' => $category,
                    'access_count' => $count,
                ];
            })
            ->values();

        $notYetViewedMateri = Materi::query()
            ->whereIn('id', $teacherMateriIds)
            ->whereNotIn('id', $viewedMateriIds)
            ->orderByDesc('created_at')
            ->take(5)
            ->get(['id', 'title', 'category', 'created_at']);

        return response()->json([
            'ok' => true,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ],
            'quiz_progress' => [
                'total_attempts' => $totalAttempts,
                'average_score' => $averageScore,
                'best_score' => $bestScore,
                'latest_score' => $latestScore,
                'trend' => $trend,
                'latest_three_avg' => $latestThreeAvg,
                'previous_three_avg' => $previousThreeAvg,
                'recent_attempts' => $recentAttempts,
            ],
            'materi_progress' => [
                'total_materi_guru' => $totalMateri,
                'total_access_logs' => $totalAccessLogs,
                'unique_materi_accessed' => $uniqueMateriAccessed,
                'coverage_percent' => $coveragePercent,
                'last_access_at' => $lastAccessAt,
                'recent_accesses' => $recentMateriAccesses,
                'category_access_summary' => $categoryAccessSummary,
                'recent_materi' => $recentMateri,
                'not_yet_viewed_materi' => $notYetViewedMateri,
                'note' => 'Log akses materi tercatat saat siswa membuka detail materi.',
            ],
        ]);
    }

    public function studentOverview(Request $request)
    {
        $student = $request->user();
        if (!$student instanceof Student) {
            return response()->json([
                'ok' => false,
                'message' => 'Akses ditolak (khusus siswa).',
            ], 403);
        }

        $results = StudentQuizResult::with('quiz:id,title')
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->get();

        $totalAttempts = $results->count();
        $averageScore = $totalAttempts > 0 ? round((float) $results->avg('score'), 2) : 0.0;
        $bestScore = $totalAttempts > 0 ? (int) $results->max('score') : 0;
        $latestScore = $totalAttempts > 0 ? (int) $results->first()->score : 0;

        $recentAttempts = $results->take(5)->values()->map(function ($item) {
            return [
                'quiz_id' => $item->quiz_id,
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
        $recentMateri = Materi::query()
            ->orderByDesc('created_at')
            ->take(5)
            ->get(['id', 'title', 'category', 'created_at']);

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

        $lastAccessAt = $materiLogs->first()?->accessed_at;

        $recentMateriAccesses = $materiLogs->take(10)->map(function ($item) {
            return [
                'materi_id' => $item->materi_id,
                'title' => $item->materi->title ?? 'Materi tanpa judul',
                'category' => $item->materi->category,
                'accessed_at' => $item->accessed_at,
            ];
        })->values();

        $categoryAccessSummary = $materiLogs
            ->map(function ($item) {
                return $item->materi->category ?? 'Tanpa Kategori';
            })
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->map(function ($count, $category) {
                return [
                    'category' => $category,
                    'access_count' => $count,
                ];
            })
            ->values();

        $notYetViewedMateri = Materi::query()
            ->whereNotIn('id', $viewedMateriIds)
            ->orderByDesc('created_at')
            ->take(5)
            ->get(['id', 'title', 'category', 'created_at']);

        return response()->json([
            'ok' => true,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ],
            'quiz_progress' => [
                'total_attempts' => $totalAttempts,
                'average_score' => $averageScore,
                'best_score' => $bestScore,
                'latest_score' => $latestScore,
                'trend' => $trend,
                'latest_three_avg' => $latestThreeAvg,
                'previous_three_avg' => $previousThreeAvg,
                'recent_attempts' => $recentAttempts,
            ],
            'materi_progress' => [
                'total_materi_available' => $totalMateri,
                'total_access_logs' => $totalAccessLogs,
                'unique_materi_accessed' => $uniqueMateriAccessed,
                'coverage_percent' => $coveragePercent,
                'last_access_at' => $lastAccessAt,
                'recent_accesses' => $recentMateriAccesses,
                'category_access_summary' => $categoryAccessSummary,
                'recent_materi' => $recentMateri,
                'not_yet_viewed_materi' => $notYetViewedMateri,
                'note' => 'Log akses materi tercatat saat siswa membuka detail materi.',
            ],
        ]);
    }

    public function describe(Request $request, int $studentId, GeminiService $gemini, PhaseFWebCompetencyService $competencyService)
    {
        $teacher = $request->user();
        if (!$teacher instanceof Teacher) {
            return response()->json([
                'ok' => false,
                'message' => 'Akses ditolak (khusus guru).',
            ], 403);
        }

        $student = Student::find($studentId);
        if (!$student) {
            return response()->json([
                'ok' => false,
                'message' => 'Siswa tidak ditemukan.',
            ], 404);
        }

        $results = StudentQuizResult::with('quiz:id,title,teacher_id')
            ->where('student_id', $student->id)
            ->whereHas('quiz', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        if ($results->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Belum ada data hasil quiz untuk siswa ini.',
            ], 422);
        }

        $totalQuiz = $results->count();
        $averageScore = round($results->avg('score'), 2);
        $bestScore = (int) $results->max('score');
        $latestScore = (int) $results->first()->score;

        $recent = $results->take(5)->values();
        $recentSummary = $recent->map(function ($item) {
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

        $teacherContext = $request->input('context');

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
            . ($teacherContext ? "Konteks dari guru: {$teacherContext}\n\n" : '')
            . "Tolong buat deskripsi perkembangan belajar siswa dalam Bahasa Indonesia dengan format WAJIB persis ini:\n"
            . "Ringkasan: <isi>\n"
            . "Kekuatan: <isi>\n"
            . "Perlu Ditingkatkan: <isi>\n"
            . "Tindak Lanjut: <isi>\n"
            . "Setiap bagian 1-2 kalimat, jelas, spesifik, suportif, dan harus menyinggung CP/ATP yang relevan. Total maksimal 220 kata.";

        $analysis = $gemini->generateText(
            $prompt,
            'Kamu adalah asisten pendidikan. Berikan evaluasi perkembangan belajar yang konstruktif, empatik, dan dapat ditindaklanjuti.'
        );

        if (!$analysis) {
            return response()->json([
                'ok' => false,
                'message' => 'Gagal mendapatkan analisis dari AI. Pastikan GEMINI_API_KEY sudah benar.',
            ], 500);
        }

        $analysisSections = $this->parseAnalysisSections($analysis);
        $qualityBadge = $this->calculateQualityBadge($analysisSections);

        $savedAnalysis = StudentProgressAnalysis::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'total_quiz' => $totalQuiz,
            'average_score' => $averageScore,
            'best_score' => $bestScore,
            'latest_score' => $latestScore,
            'teacher_context' => $teacherContext,
            'analysis' => $analysis,
        ]);

        return response()->json([
            'ok' => true,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ],
            'stats' => [
                'total_quiz' => $totalQuiz,
                'average_score' => $averageScore,
                'best_score' => $bestScore,
                'latest_score' => $latestScore,
            ],
            'analysis' => $analysis,
            'analysis_sections' => $analysisSections,
            'quality_badge' => $qualityBadge,
            'competency_profile' => $competencyProfile,
            'history_id' => $savedAnalysis->id,
            'generated_at' => $savedAnalysis->created_at,
        ]);
    }

    public function history(Request $request, int $studentId)
    {
        $teacher = $request->user();
        if (!$teacher instanceof Teacher) {
            return response()->json([
                'ok' => false,
                'message' => 'Akses ditolak (khusus guru).',
            ], 403);
        }

        $student = Student::find($studentId);
        if (!$student) {
            return response()->json([
                'ok' => false,
                'message' => 'Siswa tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $limit = (int) ($validated['limit'] ?? 20);

        $query = StudentProgressAnalysis::query()
            ->where('student_id', $student->id)
            ->where('teacher_id', $teacher->id);

        if (!empty($validated['from'])) {
            $query->where('created_at', '>=', Carbon::parse($validated['from'])->startOfDay());
        }

        if (!empty($validated['to'])) {
            $query->where('created_at', '<=', Carbon::parse($validated['to'])->endOfDay());
        }

        $history = $query
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get([
                'id',
                'teacher_id',
                'student_id',
                'total_quiz',
                'average_score',
                'best_score',
                'latest_score',
                'teacher_context',
                'analysis',
                'created_at',
            ]);

        return response()->json([
            'ok' => true,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ],
            'count' => $history->count(),
            'history' => $history,
        ]);
    }

    public function historyDetail(Request $request, int $id)
    {
        $teacher = $request->user();
        if (!$teacher instanceof Teacher) {
            return response()->json([
                'ok' => false,
                'message' => 'Akses ditolak (khusus guru).',
            ], 403);
        }

        $history = StudentProgressAnalysis::with('student:id,name,email')
            ->where('id', $id)
            ->where('teacher_id', $teacher->id)
            ->first();

        if (!$history) {
            return response()->json([
                'ok' => false,
                'message' => 'Histori analisis tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $history,
            'analysis_sections' => $this->parseAnalysisSections($history->analysis),
            'quality_badge' => $this->calculateQualityBadge($this->parseAnalysisSections($history->analysis)),
        ]);
    }

    public function studentDescribe(Request $request, GeminiService $gemini, PhaseFWebCompetencyService $competencyService)
    {
        $student = $request->user();
        if (!$student instanceof Student) {
            return response()->json([
                'ok' => false,
                'message' => 'Akses ditolak (khusus siswa).',
            ], 403);
        }

        $validated = $request->validate([
            'context' => ['nullable', 'string', 'max:500'],
        ]);

        $results = StudentQuizResult::with('quiz:id,title,teacher_id')
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->get();

        if ($results->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Belum ada data hasil quiz untuk dianalisis.',
            ], 422);
        }

        $totalQuiz = $results->count();
        $averageScore = round((float) $results->avg('score'), 2);
        $bestScore = (int) $results->max('score');
        $latestScore = (int) $results->first()->score;

        $recentSummary = $results->take(5)->values()->map(function ($item) {
            $quizTitle = $item->quiz->title ?? 'Quiz tanpa judul';
            return "- {$quizTitle}: skor {$item->score} ({$item->created_at->format('Y-m-d H:i')})";
        })->implode("\n");

        $materiLogs = StudentMateriLog::with('materi:id,title,category,description')
            ->where('student_id', $student->id)
            ->orderByDesc('accessed_at')
            ->get();
        $totalMateri = Materi::count();
        $uniqueMateriAccessed = $materiLogs->pluck('materi_id')->unique()->count();
        $coveragePercent = $totalMateri > 0
            ? round(($uniqueMateriAccessed / $totalMateri) * 100, 2)
            : 0.0;

        $competencyProfile = $competencyService->buildProfile($results, $materiLogs);

        $studentContext = trim((string) ($validated['context'] ?? ''));

        $prompt = "Berikut data perkembangan belajar saya sebagai siswa:\n"
            . "Nama: {$student->name}\n"
            . "Email: {$student->email}\n"
            . "Total quiz dikerjakan: {$totalQuiz}\n"
            . "Rata-rata skor: {$averageScore}\n"
            . "Skor terbaik: {$bestScore}\n"
            . "Skor terbaru: {$latestScore}\n"
            . "Cakupan materi dibaca: {$coveragePercent}%\n"
            . "Ringkasan 5 quiz terakhir:\n{$recentSummary}\n\n"
            . "Peta keterhubungan soal dan materi berdasarkan CP/ATP Pemrograman Website Fase F:\n"
            . $competencyProfile['context_text'] . "\n\n"
            . ($studentContext !== '' ? "Konteks tambahan dari siswa: {$studentContext}\n\n" : '')
            . "Tolong buat feedback belajar dalam Bahasa Indonesia dengan format WAJIB persis ini:\n"
            . "Ringkasan: <isi>\n"
            . "Kekuatan: <isi>\n"
            . "Perlu Ditingkatkan: <isi>\n"
            . "Tindak Lanjut: <isi>\n"
            . "Setiap bagian 1-2 kalimat, suportif, jelas, praktis, dan terkait CP/ATP yang paling menonjol. Total maksimal 220 kata.";

        $analysis = $gemini->generateText(
            $prompt,
            'Kamu adalah mentor belajar yang suportif. Berikan umpan balik yang membangun dan bisa langsung dipraktikkan siswa.'
        );

        if (!$analysis) {
            return response()->json([
                'ok' => false,
                'message' => 'Gagal mendapatkan feedback AI. Pastikan GEMINI_API_KEY valid.',
            ], 500);
        }

        $analysisSections = $this->parseAnalysisSections($analysis);
        $qualityBadge = $this->calculateQualityBadge($analysisSections);

        $ownerTeacherId = $results
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
                'total_quiz' => $totalQuiz,
                'average_score' => $averageScore,
                'best_score' => $bestScore,
                'latest_score' => $latestScore,
                'teacher_context' => $studentContext !== '' ? 'Konteks siswa: ' . $studentContext : null,
                'analysis' => $analysis,
            ]);
        }

        return response()->json([
            'ok' => true,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ],
            'stats' => [
                'total_quiz' => $totalQuiz,
                'average_score' => $averageScore,
                'best_score' => $bestScore,
                'latest_score' => $latestScore,
                'materi_coverage_percent' => $coveragePercent,
            ],
            'analysis' => $analysis,
            'analysis_sections' => $analysisSections,
            'quality_badge' => $qualityBadge,
            'competency_profile' => $competencyProfile,
            'history_id' => $savedAnalysis?->id,
            'generated_at' => $savedAnalysis?->created_at,
        ]);
    }

    public function studentHistory(Request $request)
    {
        $student = $request->user();
        if (!$student instanceof Student) {
            return response()->json([
                'ok' => false,
                'message' => 'Akses ditolak (khusus siswa).',
            ], 403);
        }

        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $limit = (int) ($validated['limit'] ?? 20);

        $query = StudentProgressAnalysis::query()
            ->where('student_id', $student->id);

        if (!empty($validated['from'])) {
            $query->where('created_at', '>=', Carbon::parse($validated['from'])->startOfDay());
        }

        if (!empty($validated['to'])) {
            $query->where('created_at', '<=', Carbon::parse($validated['to'])->endOfDay());
        }

        $history = $query
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get([
                'id',
                'student_id',
                'total_quiz',
                'average_score',
                'best_score',
                'latest_score',
                'teacher_context',
                'analysis',
                'created_at',
            ]);

        return response()->json([
            'ok' => true,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ],
            'count' => $history->count(),
            'history' => $history,
        ]);
    }

    public function studentHistoryDetail(Request $request, int $id)
    {
        $student = $request->user();
        if (!$student instanceof Student) {
            return response()->json([
                'ok' => false,
                'message' => 'Akses ditolak (khusus siswa).',
            ], 403);
        }

        $history = StudentProgressAnalysis::query()
            ->where('id', $id)
            ->where('student_id', $student->id)
            ->first();

        if (!$history) {
            return response()->json([
                'ok' => false,
                'message' => 'Histori feedback AI tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $history,
            'analysis_sections' => $this->parseAnalysisSections($history->analysis),
            'quality_badge' => $this->calculateQualityBadge($this->parseAnalysisSections($history->analysis)),
        ]);
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
}
