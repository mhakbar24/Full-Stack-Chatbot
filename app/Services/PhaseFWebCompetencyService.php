<?php

namespace App\Services;

use Illuminate\Support\Collection;

class PhaseFWebCompetencyService
{
    public function materiTopicOptions(): array
    {
        return [
            [
                'key' => 'html_semantic_layout',
                'name' => 'HTML Semantik dan Struktur Layout',
                'focus' => 'penyusunan kerangka halaman dengan elemen semantik yang tepat',
            ],
            [
                'key' => 'css_responsive_design',
                'name' => 'CSS Responsif dengan Flexbox/Grid',
                'focus' => 'pembuatan layout responsif untuk desktop dan mobile',
            ],
            [
                'key' => 'javascript_dom_events',
                'name' => 'JavaScript DOM dan Event Handling',
                'focus' => 'interaksi dinamis menggunakan event dan manipulasi elemen',
            ],
            [
                'key' => 'form_validation_basic',
                'name' => 'Form dan Validasi Input Dasar',
                'focus' => 'validasi input sisi frontend dan backend sederhana',
            ],
            [
                'key' => 'http_api_integration',
                'name' => 'Integrasi HTTP API (JSON)',
                'focus' => 'alur request-response, fetch/axios, dan penanganan error',
            ],
            [
                'key' => 'backend_crud_laravel',
                'name' => 'Backend CRUD Laravel',
                'focus' => 'implementasi CRUD, validasi, dan relasi data dasar',
            ],
            [
                'key' => 'auth_sanctum_basic',
                'name' => 'Autentikasi Dasar dengan Sanctum',
                'focus' => 'login, token, proteksi route, dan praktik keamanan dasar',
            ],
            [
                'key' => 'debug_testing_web',
                'name' => 'Debugging dan Testing Aplikasi Web',
                'focus' => 'analisis error log, pengujian fungsional, dan perbaikan bertahap',
            ],
        ];
    }

    /**
     * CP/ATP acuan ringkas untuk Pemrograman Website Fase F.
     */
    private function catalog(): array
    {
        return [
            'html_semantic' => [
                'name' => 'Struktur HTML Semantik',
                'cp' => 'Memahami struktur dan elemen HTML semantik untuk membangun halaman web yang terorganisir.',
                'atp' => 'Menyusun layout halaman menggunakan heading, section, article, nav, dan form dasar.',
                'keywords' => ['html', 'semantic', 'semantik', 'tag', 'form', 'input', 'struktur halaman'],
            ],
            'css_layout' => [
                'name' => 'CSS Layout dan Responsif',
                'cp' => 'Menerapkan gaya visual dan tata letak responsif pada antarmuka web.',
                'atp' => 'Menggunakan selector, box model, flexbox/grid, media query, dan konsistensi desain.',
                'keywords' => ['css', 'layout', 'flexbox', 'grid', 'responsive', 'media query', 'box model', 'styling'],
            ],
            'javascript_dom' => [
                'name' => 'JavaScript Dasar dan DOM',
                'cp' => 'Membangun interaksi dinamis pada halaman web menggunakan JavaScript.',
                'atp' => 'Menggunakan variabel, kondisi, perulangan, function, event, dan manipulasi DOM.',
                'keywords' => ['javascript', 'js', 'dom', 'event', 'function', 'loop', 'kondisi', 'interaktif'],
            ],
            'http_api' => [
                'name' => 'HTTP, API, dan Integrasi Data',
                'cp' => 'Memahami alur komunikasi client-server dan integrasi data melalui API.',
                'atp' => 'Menggunakan request/response, method HTTP, JSON, fetch/axios, serta penanganan error sederhana.',
                'keywords' => ['api', 'http', 'json', 'fetch', 'axios', 'request', 'response', 'rest'],
            ],
            'backend_database' => [
                'name' => 'Backend dan Basis Data',
                'cp' => 'Mengembangkan logika backend dan pengelolaan data untuk aplikasi web.',
                'atp' => 'Membuat CRUD, validasi input, relasi data sederhana, dan autentikasi dasar.',
                'keywords' => ['backend', 'database', 'mysql', 'crud', 'laravel', 'auth', 'validasi', 'query'],
            ],
            'testing_debugging' => [
                'name' => 'Debugging, Testing, dan Kualitas',
                'cp' => 'Mengevaluasi dan meningkatkan kualitas aplikasi web melalui debugging dan pengujian.',
                'atp' => 'Melakukan analisis error log, uji fungsional, dan perbaikan bertahap berbasis temuan.',
                'keywords' => ['debug', 'testing', 'error', 'bug', 'log', 'uji', 'quality', 'validasi'],
            ],
        ];
    }

    public function competencyOptions(): array
    {
        return collect($this->catalog())->map(function ($meta, $key) {
            return [
                'key' => $key,
                'name' => $meta['name'],
                'cp' => $meta['cp'],
                'atp' => $meta['atp'],
            ];
        })->values()->toArray();
    }

    public function getCompetencyByKey(?string $key): ?array
    {
        if (!$key) {
            return null;
        }

        $catalog = $this->catalog();
        if (!array_key_exists($key, $catalog)) {
            return null;
        }

        return [
            'key' => $key,
            'name' => $catalog[$key]['name'],
            'cp' => $catalog[$key]['cp'],
            'atp' => $catalog[$key]['atp'],
            'keywords' => $catalog[$key]['keywords'],
        ];
    }

    public function renderCatalogContext(?string $preferredKey = null): string
    {
        $catalog = $this->catalog();

        if ($preferredKey && array_key_exists($preferredKey, $catalog)) {
            $item = $catalog[$preferredKey];
            return '- ' . $item['name'] . ' | CP: ' . $item['cp'] . ' | ATP: ' . $item['atp'];
        }

        return collect($catalog)->map(function ($item) {
            return '- ' . $item['name'] . ' | CP: ' . $item['cp'] . ' | ATP: ' . $item['atp'];
        })->implode("\n");
    }

    public function buildProfile(Collection $quizResults, Collection $materiLogs): array
    {
        $catalog = $this->catalog();

        $rows = collect($catalog)->map(function ($meta, $key) {
            return [
                'key' => $key,
                'name' => $meta['name'],
                'cp' => $meta['cp'],
                'atp' => $meta['atp'],
                'quiz_attempts' => 0,
                'quiz_total_score' => 0.0,
                'quiz_avg_score' => 0.0,
                'materi_accesses' => 0,
                'alignment' => 'belum_terhubung',
            ];
        });

        foreach ($quizResults as $row) {
            $texts = [
                $row->quiz->title ?? '',
                $row->quiz->description ?? '',
            ];
            $key = $this->inferCompetencyKey($texts, $catalog) ?? 'javascript_dom';

            $entry = $rows->get($key);
            if (!$entry) {
                continue;
            }

            $entry['quiz_attempts'] += 1;
            $entry['quiz_total_score'] += (float) ($row->score ?? 0);
            $rows->put($key, $entry);
        }

        foreach ($materiLogs as $row) {
            $texts = [
                $row->materi->title ?? '',
                $row->materi->category ?? '',
                $row->materi->description ?? '',
            ];
            $key = $this->inferCompetencyKey($texts, $catalog) ?? 'html_semantic';

            $entry = $rows->get($key);
            if (!$entry) {
                continue;
            }

            $entry['materi_accesses'] += 1;
            $rows->put($key, $entry);
        }

        $rows = $rows->map(function ($row) {
            if ((int) $row['quiz_attempts'] > 0) {
                $row['quiz_avg_score'] = round((float) $row['quiz_total_score'] / (int) $row['quiz_attempts'], 2);
            }

            if ((int) $row['quiz_attempts'] > 0 && (int) $row['materi_accesses'] > 0) {
                $row['alignment'] = 'terhubung';
            } elseif ((int) $row['quiz_attempts'] > 0 || (int) $row['materi_accesses'] > 0) {
                $row['alignment'] = 'parsial';
            }

            unset($row['quiz_total_score']);
            return $row;
        })->values();

        $activeRows = $rows->filter(function ($row) {
            return (int) $row['quiz_attempts'] > 0 || (int) $row['materi_accesses'] > 0;
        })->values();

        $contextLines = $activeRows->map(function ($row) {
            return '- ' . $row['name']
                . ' | CP: ' . $row['cp']
                . ' | ATP: ' . $row['atp']
                . ' | Data: attempt quiz=' . $row['quiz_attempts']
                . ', rata-rata skor=' . number_format((float) $row['quiz_avg_score'], 2)
                . ', akses materi=' . $row['materi_accesses']
                . ', keterhubungan=' . $row['alignment'];
        })->implode("\n");

        if ($contextLines === '') {
            $contextLines = '- Belum ada data aktivitas yang cukup untuk memetakan kompetensi CP/ATP secara spesifik.';
        }

        $alignedCount = $rows->where('alignment', 'terhubung')->count();
        $partialCount = $rows->where('alignment', 'parsial')->count();

        return [
            'rows' => $rows->toArray(),
            'active_rows' => $activeRows->toArray(),
            'context_text' => $contextLines,
            'alignment_summary' => [
                'aligned_count' => $alignedCount,
                'partial_count' => $partialCount,
                'total_competencies' => $rows->count(),
            ],
        ];
    }

    private function inferCompetencyKey(array $texts, array $catalog): ?string
    {
        $joined = mb_strtolower(trim(implode(' ', $texts)), 'UTF-8');
        if ($joined === '') {
            return null;
        }

        $bestKey = null;
        $bestScore = 0;

        foreach ($catalog as $key => $meta) {
            $score = 0;
            foreach ($meta['keywords'] as $keyword) {
                if (str_contains($joined, mb_strtolower($keyword, 'UTF-8'))) {
                    $score++;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestKey = $key;
            }
        }

        return $bestScore > 0 ? $bestKey : null;
    }
}
