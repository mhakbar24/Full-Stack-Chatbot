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
            'topicOptions' => $competencyService->materiTopicOptions(),
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
            'icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('materi_images', 'public');
        }

        $iconPath = null;
        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store('materi_icons', 'public');
        }

        Materi::create([
            'teacher_id' => $teacher->id,
            'title' => $validated['title'],
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'] ?? null,
            'image' => $imagePath,
            'icon' => $iconPath,
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
        $topicFocus = 'konsep inti dan implementasi praktis';
        $depth = (string) ($validated['depth'] ?? 'menengah');
        $competencyKey = $validated['competency_key'] ?? null;
        $selectedCompetency = $competencyService->getCompetencyByKey($competencyKey);
        $catalogContext = $competencyService->renderCatalogContext($competencyKey);

        $prompt = "Buat materi pembelajaran pemrograman web fase F untuk guru dengan topik: {$topic}.\n"
            . "Fokus topik: {$topicFocus}.\n"
            . "Kedalaman materi: {$depth}.\n"
            . "Target pembaca: siswa SMK (fase F) yang masih belajar dari dasar.\n"
            . "Acuan CP/ATP:\n{$catalogContext}\n\n"
            . ($selectedCompetency ? "Kompetensi prioritas: {$selectedCompetency['name']}.\n" : '')
            . "Kembalikan HANYA JSON valid tanpa markdown dengan struktur:\n"
            . "{\n"
            . "  \"title\": \"...\",\n"
            . "  \"category\": \"...\",\n"
            . "  \"isi_materi\": \"...\"\n"
            . "}\n"
            . "Isi isi_materi HARUS berupa isi materi lengkap siap ajar, bukan deskripsi materi.\n"
            . "Gunakan struktur bagian berikut di dalam isi_materi dengan paragraf nyata:\n"
            . "1) Tujuan Pembelajaran\n"
            . "2) Konsep Inti\n"
            . "3) Langkah Praktik\n"
            . "4) Contoh Kode\n"
            . "5) Latihan Siswa\n"
            . "6) Ringkasan\n"
            . "Aturan gaya bahasa:\n"
            . "- Gunakan bahasa Indonesia sederhana, kalimat pendek, dan hindari istilah teknis yang rumit.\n"
            . "- Jika harus memakai istilah teknis, langsung beri penjelasan singkat dalam tanda kurung.\n"
            . "- Beri contoh yang dekat dengan kehidupan siswa SMK (misal form login, katalog produk, halaman profil).\n"
            . "- Pada Langkah Praktik, tulis langkah bernomor yang bisa langsung diikuti siswa.\n"
            . "- Pada Contoh Kode, gunakan contoh minimal namun benar dan mudah dipahami.\n"
            . "Panjang minimal 220 kata, bahasa Indonesia yang jelas, aplikatif, dan mudah diajarkan.";

        $raw = $gemini->generateText(
            $prompt,
            'Kamu penyusun materi untuk siswa SMK fase F. Tulis materi yang mudah dipahami pemula, praktis, dan tidak bertele-tele. Jawab JSON murni saja tanpa teks tambahan.',
            true
        );

        $payload = $raw ? $this->parseMateriJson($raw) : null;

        if (!$this->isValidGeneratedMateriPayload($payload) || !$this->isDetailedGeneratedMateri((string) ($payload['description'] ?? ''))) {
            $fallbackPrompt = "Topik materi: {$topic} ({$topicFocus}). Balas HANYA JSON valid: "
                . '{"title":"...","category":"...","isi_materi":"..."}'
                . " dengan konten sesuai CP/ATP pemrograman web fase F level {$depth}. "
                . "Isi_materi harus berisi materi lengkap siap ajar (Tujuan Pembelajaran, Konsep Inti, Langkah Praktik, Contoh Kode, Latihan Siswa, Ringkasan) minimal 220 kata."
                . " Gunakan bahasa sederhana untuk siswa SMK pemula, langkah praktis bernomor, dan contoh kontekstual sehari-hari.";

            $rawFallback = $gemini->generateText(
                $fallbackPrompt,
                'JSON murni tanpa markdown. Fokus pada materi yang mudah dipahami siswa SMK pemula.',
                true
            );

            if ($rawFallback) {
                $payload = $this->parseMateriJson($rawFallback);
            }
        }

        if (!$this->isValidGeneratedMateriPayload($payload) || !$this->isDetailedGeneratedMateri((string) ($payload['description'] ?? ''))) {
            return redirect()->route('guru.materis')
            ->with('status', 'Generate materi AI gagal menghasilkan isi materi lengkap. Silakan coba lagi.');
        }

        Materi::create([
            'teacher_id' => $teacher->id,
            'title' => mb_substr((string) $payload['title'], 0, 255),
            'category' => mb_substr((string) ($payload['category'] ?? 'CP/ATP Fase F'), 0, 255),
            'description' => (string) $payload['description'],
            'image' => null,
            'icon' => null,
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
            'icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
        ]);

        if ($request->hasFile('image')) {
            if ($materi->image) {
                Storage::disk('public')->delete($materi->image);
            }
            $validated['image'] = $request->file('image')->store('materi_images', 'public');
        }

        if ($request->hasFile('icon')) {
            if ($materi->icon) {
                Storage::disk('public')->delete($materi->icon);
            }
            $validated['icon'] = $request->file('icon')->store('materi_icons', 'public');
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
        if ($materi->icon) {
            Storage::disk('public')->delete($materi->icon);
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
            'description' => (string) ($payload['isi_materi'] ?? $payload['description'] ?? $payload['deskripsi'] ?? $payload['content'] ?? ''),
        ];
    }

    private function isValidGeneratedMateriPayload(?array $payload): bool
    {
        return is_array($payload)
            && trim((string) ($payload['title'] ?? '')) !== ''
            && trim((string) ($payload['description'] ?? '')) !== '';
    }

    private function isDetailedGeneratedMateri(string $description): bool
    {
        $normalized = mb_strtolower(trim(strip_tags($description)), 'UTF-8');
        if ($normalized === '') {
            return false;
        }

        $wordCount = str_word_count($normalized);
        if ($wordCount < 220) {
            return false;
        }

        $requiredSectionHints = [
            'tujuan pembelajaran',
            'konsep inti',
            'langkah praktik',
            'contoh kode',
            'latihan',
            'ringkasan',
        ];

        $hit = 0;
        foreach ($requiredSectionHints as $hint) {
            if (str_contains($normalized, $hint)) {
                $hit++;
            }
        }

        return $hit >= 4;
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
