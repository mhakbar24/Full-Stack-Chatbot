<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>PDF Progres Siswa untuk Guru</title>
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
    </style>
</head>
<body>
    <h1>Detail Progres Siswa (Guru)</h1>
    <div class="meta">Nama: {{ $student->name }} | Email: {{ $student->email }} | Rentang: {{ $weeks }} minggu | Dicetak: {{ $generatedAt->format('d M Y H:i') }}</div>

    <table class="grid">
        <tr>
            <td><span class="label">Total Attempt Quiz</span><span class="value">{{ $summary['total_attempts'] }}</span></td>
            <td><span class="label">Rata-rata Skor</span><span class="value">{{ number_format($summary['average_score'], 2) }}</span></td>
            <td><span class="label">Skor Terbaru</span><span class="value">{{ $summary['latest_score'] }}</span></td>
            <td><span class="label">Cakupan Materi Guru</span><span class="value">{{ number_format($summary['materi_coverage'], 2) }}%</span></td>
        </tr>
    </table>

    <div class="section">
        <h2>Tren Mingguan (Quiz + Materi)</h2>
        <table>
            <thead><tr><th>Minggu</th><th>Rata-rata Quiz</th><th>Attempt Quiz</th><th>Akses Materi</th></tr></thead>
            <tbody>
                @forelse($weeklyProgress as $row)
                    <tr>
                        <td>{{ $row['week_label'] }}</td>
                        <td>{{ number_format($row['quiz_avg'], 2) }}</td>
                        <td>{{ $row['quiz_attempts'] }}</td>
                        <td>{{ $row['materi_accesses'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Belum ada data progres mingguan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Aktivitas Materi per Kategori</h2>
        <table>
            <thead><tr><th>Kategori</th><th>Total Akses</th><th>Materi Unik Diakses</th></tr></thead>
            <tbody>
                @forelse($categorySummary as $item)
                    <tr>
                        <td>{{ $item['category'] }}</td>
                        <td>{{ $item['access_count'] }}</td>
                        <td>{{ $item['unique_materi'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Belum ada aktivitas materi per kategori.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
