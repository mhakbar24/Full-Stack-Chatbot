<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ringkasan Progres Siswa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body{margin:0;font-family:'Space Grotesk',sans-serif;background:#f6fbff;color:#17314b;}
        .wrap{max-width:1080px;margin:0 auto;padding:20px;}
        .head{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;}
        .btn{text-decoration:none;border:0;border-radius:10px;padding:9px 12px;display:inline-block;font-weight:700;}
        .btn-main{background:#ff6b35;color:#fff;} .btn-alt{background:#e5eef9;color:#26496f;}
        .card{background:#fff;border:1px solid #d9e7f4;border-radius:14px;padding:16px;margin-top:14px;}
        .grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;}
        .stat{padding:12px;border:1px solid #e8eff8;border-radius:11px;background:#fbfdff;}
        .label{font-size:.82rem;color:#5b7593;}.value{font-size:1.25rem;font-weight:700;}
        .pill{display:inline-block;padding:4px 9px;border-radius:999px;font-size:.75rem;font-weight:700;}
        .up{background:#dcfce7;color:#166534;} .flat{background:#fef3c7;color:#92400e;} .down{background:#fee2e2;color:#991b1b;}
        .feedback-box{margin-top:12px;padding:12px;border-radius:12px;border:1px solid #d9e7f4;background:#f8fbff;}
        .feedback-box h3{margin:0 0 8px;}
        .feedback-item{margin-top:8px;padding:9px;border-radius:10px;background:#fff;border:1px solid #e8eff8;}
        .feedback-competency{margin-top:10px;border:1px solid #e8eff8;border-radius:10px;background:#fff;overflow:hidden;}
        .feedback-competency table{width:100%;border-collapse:collapse;min-width:520px;}
        .feedback-competency th,.feedback-competency td{padding:8px;border-bottom:1px solid #edf2f8;text-align:left;font-size:.88rem;vertical-align:top;}
        .feedback-pill{display:inline-block;padding:4px 8px;border-radius:999px;font-size:.75rem;font-weight:700;margin-left:8px;}
        .feedback-form{margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;}
        .feedback-input{border:1px solid #c7d9ee;border-radius:10px;padding:8px 10px;min-width:260px;font-family:inherit;}
        .feedback-btn{border:0;border-radius:10px;padding:9px 12px;background:#0f766e;color:#fff;font-weight:700;cursor:pointer;}
        .feedback-error{margin-top:8px;color:#991b1b;font-weight:700;}
        table{width:100%;border-collapse:collapse;} th,td{padding:10px 8px;border-bottom:1px solid #edf2f8;text-align:left;}
        @media (max-width:900px){.grid{grid-template-columns:1fr 1fr;}}
        @media (max-width:700px){.wrap{padding:12px;}table{min-width:650px;}.table-wrap{overflow-x:auto;}}
    </style>
</head>
<body>
<main class="wrap">
    <div class="head">
        <div>
            <h1 style="margin:0;">Ringkasan Progres Saya</h1>
            <div style="color:#5a7593;">{{ $student->name }} ({{ $student->email }})</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('siswa.dashboard') }}" class="btn btn-alt">Dashboard</a>
            <a href="{{ route('siswa.progress') }}" class="btn btn-main">Progres Mingguan</a>
            <a href="{{ route('siswa.history.ai') }}" class="btn btn-alt">Riwayat Feedback AI</a>
            <a href="{{ route('siswa.progress.overview.pdf') }}" class="btn btn-alt">Export PDF</a>
        </div>
    </div>

    <section class="card">
        <div class="grid">
            <div class="stat"><div class="label">Total Attempt Quiz</div><div class="value">{{ $summary['total_attempts'] }}</div></div>
            <div class="stat"><div class="label">Rata-rata Skor</div><div class="value">{{ number_format($summary['average_score'], 2) }}</div></div>
            <div class="stat"><div class="label">Skor Terbaik</div><div class="value">{{ $summary['best_score'] }}</div></div>
            <div class="stat"><div class="label">Skor Terbaru</div><div class="value">{{ $summary['latest_score'] }}</div></div>
            <div class="stat"><div class="label">Materi Tersedia</div><div class="value">{{ $summary['total_materi'] }}</div></div>
            <div class="stat"><div class="label">Total Akses Materi</div><div class="value">{{ $summary['total_access_logs'] }}</div></div>
            <div class="stat"><div class="label">Materi Unik Dibaca</div><div class="value">{{ $summary['unique_materi_accessed'] }}</div></div>
            <div class="stat"><div class="label">Cakupan Materi</div><div class="value">{{ number_format($summary['coverage_percent'], 2) }}%</div></div>
        </div>
        <div style="margin-top:10px;">
            @php
                $trendClass = $summary['trend'] === 'meningkat' ? 'up' : ($summary['trend'] === 'menurun' ? 'down' : 'flat');
            @endphp
            <span class="pill {{ $trendClass }}">Tren Quiz: {{ ucfirst($summary['trend']) }}</span>
        </div>

        <div class="feedback-box">
            <h3>Feedback AI Belajar Saya</h3>
            <form method="POST" action="{{ route('siswa.progress.overview.ai') }}" class="feedback-form" id="siswa-ai-feedback-form">
                @csrf
                <input type="text" name="context" class="feedback-input" placeholder="Konteks opsional (mis. sulit konsisten latihan)">
                <button type="submit" class="feedback-btn" id="siswa-ai-feedback-btn">Dapatkan Feedback AI</button>
            </form>

            @if(!empty($aiFeedback) && is_array($aiFeedback))
                @if(($aiFeedback['status'] ?? '') === 'error')
                    <div class="feedback-error">{{ $aiFeedback['message'] ?? 'Terjadi kesalahan.' }}</div>
                @else
                    <div style="margin-top:10px;">
                        <strong>{{ $aiFeedback['message'] ?? 'Feedback AI tersedia.' }}</strong>
                        @if(!empty($aiFeedback['history_id']))
                            <a href="{{ route('siswa.history.ai.detail', $aiFeedback['history_id']) }}" style="margin-left:8px;font-weight:700;color:#1f4d80;">Lihat di Riwayat</a>
                        @endif
                        @if(!empty($aiFeedback['quality_badge']['label']))
                            <span class="feedback-pill" style="background:{{ $aiFeedback['quality_badge']['bg_color'] }};color:{{ $aiFeedback['quality_badge']['color'] }};">
                                Kualitas: {{ $aiFeedback['quality_badge']['label'] }}
                            </span>
                        @endif
                    </div>
                    @if(!empty($aiFeedback['analysis_sections']))
                        <div class="feedback-item"><strong>Ringkasan</strong><br>{{ $aiFeedback['analysis_sections']['ringkasan'] ?? '-' }}</div>
                        <div class="feedback-item"><strong>Kekuatan</strong><br>{{ $aiFeedback['analysis_sections']['kekuatan'] ?? '-' }}</div>
                        <div class="feedback-item"><strong>Perlu Ditingkatkan</strong><br>{{ $aiFeedback['analysis_sections']['perlu_ditingkatkan'] ?? '-' }}</div>
                        <div class="feedback-item"><strong>Tindak Lanjut</strong><br>{{ $aiFeedback['analysis_sections']['tindak_lanjut'] ?? '-' }}</div>
                    @endif
                    @if(!empty($aiFeedback['competency_profile']['active_rows']))
                        <div class="feedback-competency table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Kompetensi</th>
                                        <th>CP/ATP Ringkas</th>
                                        <th>Quiz</th>
                                        <th>Materi</th>
                                        <th>Keterhubungan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($aiFeedback['competency_profile']['active_rows'] as $row)
                                        <tr>
                                            <td><strong>{{ $row['name'] }}</strong></td>
                                            <td>{{ $row['cp'] }} / {{ $row['atp'] }}</td>
                                            <td>{{ $row['quiz_attempts'] }} attempt<br>Avg {{ number_format((float) $row['quiz_avg_score'], 2) }}</td>
                                            <td>{{ $row['materi_accesses'] }} akses</td>
                                            <td>{{ ucfirst(str_replace('_', ' ', $row['alignment'])) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif
            @endif
        </div>
    </section>

    <section class="card table-wrap">
        <h2 style="margin:0 0 10px;">10 Hasil Quiz Terakhir</h2>
        <table>
            <thead>
                <tr><th>Quiz</th><th>Skor</th><th>Waktu</th></tr>
            </thead>
            <tbody>
                @forelse($recentAttempts as $item)
                    <tr>
                        <td>{{ $item['quiz_title'] }}</td>
                        <td>{{ $item['score'] }}</td>
                        <td>{{ $item['taken_at']->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Belum ada hasil quiz.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="card table-wrap">
        <h2 style="margin:0 0 10px;">10 Aktivitas Materi Terakhir</h2>
        <table>
            <thead>
                <tr><th>Materi</th><th>Kategori</th><th>Waktu Akses</th></tr>
            </thead>
            <tbody>
                @forelse($recentAccesses as $item)
                    <tr>
                        <td>{{ $item['title'] }}</td>
                        <td>{{ $item['category'] }}</td>
                        <td>{{ $item['accessed_at']->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Belum ada aktivitas materi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="card">
        <h2 style="margin:0 0 10px;">Rekomendasi Materi Belum Dibaca</h2>
        @forelse($notYetViewedMateri as $materi)
            <div style="padding:10px;border:1px solid #e8eff8;border-radius:10px;background:#fbfdff;margin-bottom:8px;">
                <strong>{{ $materi->title }}</strong>
                <div style="color:#5a7593;font-size:.9rem;">Kategori: {{ $materi->category ?: 'Umum' }} • {{ $materi->created_at->format('d M Y') }}</div>
            </div>
        @empty
            <div>Semua materi sudah pernah kamu baca. Pertahankan konsistensi belajar.</div>
        @endforelse
    </section>
</main>
<script>
    (function () {
        var form = document.getElementById('siswa-ai-feedback-form');
        var button = document.getElementById('siswa-ai-feedback-btn');
        if (!form || !button) {
            return;
        }
        form.addEventListener('submit', function () {
            button.disabled = true;
            button.textContent = 'Memproses...';
        });
    })();
</script>
</body>
</html>
