<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>PDF Ringkasan Progres Siswa</title>
    <style>
        body{font-family:DejaVu Sans, sans-serif;color:#1f2937;font-size:12px;}
        h1,h2{margin:0 0 8px;}
        .meta{margin-bottom:12px;color:#4b5563;}
        .grid{width:100%;border-collapse:collapse;margin:10px 0 14px;}
        .grid td{border:1px solid #d1d5db;padding:8px;vertical-align:top;width:25%;}
        .label{font-size:10px;color:#6b7280;display:block;}
        .value{font-size:14px;font-weight:700;}
        table{width:100%;border-collapse:collapse;margin-top:8px;}
        th,td{border:1px solid #d1d5db;padding:6px;text-align:left;}
        th{background:#f3f4f6;}
        .section{margin-top:14px;}
        .item{border:1px solid #d1d5db;padding:7px;margin-bottom:6px;}
    </style>
</head>
<body>
    <h1>Ringkasan Progres Siswa</h1>
    <div class="meta">Nama: {{ $student->name }} | Email: {{ $student->email }} | Dicetak: {{ $generatedAt->format('d M Y H:i') }}</div>

    <table class="grid">
        <tr>
            <td><span class="label">Total Attempt Quiz</span><span class="value">{{ $summary['total_attempts'] }}</span></td>
            <td><span class="label">Rata-rata Skor</span><span class="value">{{ number_format($summary['average_score'], 2) }}</span></td>
            <td><span class="label">Skor Terbaik</span><span class="value">{{ $summary['best_score'] }}</span></td>
            <td><span class="label">Skor Terbaru</span><span class="value">{{ $summary['latest_score'] }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Materi Tersedia</span><span class="value">{{ $summary['total_materi'] }}</span></td>
            <td><span class="label">Total Akses Materi</span><span class="value">{{ $summary['total_access_logs'] }}</span></td>
            <td><span class="label">Materi Unik Dibaca</span><span class="value">{{ $summary['unique_materi_accessed'] }}</span></td>
            <td><span class="label">Cakupan Materi</span><span class="value">{{ number_format($summary['coverage_percent'], 2) }}%</span></td>
        </tr>
    </table>

    <div class="section">
        <h2>10 Hasil Quiz Terakhir</h2>
        <table>
            <thead><tr><th>Quiz</th><th>Skor</th><th>Waktu</th></tr></thead>
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
    </div>

    <div class="section">
        <h2>10 Aktivitas Materi Terakhir</h2>
        <table>
            <thead><tr><th>Materi</th><th>Kategori</th><th>Waktu Akses</th></tr></thead>
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
    </div>

    <div class="section">
        <h2>Rekomendasi Materi Belum Dibaca</h2>
        @forelse($notYetViewedMateri as $materi)
            <div class="item">
                <strong>{{ $materi->title }}</strong><br>
                <span>Kategori: {{ $materi->category ?: 'Umum' }} | {{ $materi->created_at->format('d M Y') }}</span>
            </div>
        @empty
            <div class="item">Semua materi sudah pernah dibaca.</div>
        @endforelse
    </div>
</body>
</html>
