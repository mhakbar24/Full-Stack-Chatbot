<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Progres Siswa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Space Grotesk', sans-serif;
            color: #17304a;
            background: linear-gradient(130deg, #f5fbff, #eef7ff 38%, #fff6eb);
            min-height: 100vh;
        }

        .wrap { max-width: 1080px; margin: 0 auto; padding: 20px; }
        .head { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }

        .btn {
            text-decoration: none;
            border: 0;
            border-radius: 10px;
            padding: 9px 12px;
            display: inline-block;
            font-weight: 700;
        }

        .btn-main { background: #ff6b35; color: #fff; }
        .btn-alt { background: #e5eef9; color: #26496f; }

        .card {
            background: #fff;
            border: 1px solid #d9e7f4;
            border-radius: 14px;
            padding: 16px;
            margin-top: 14px;
        }

        .filters {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        select {
            border: 1px solid #c7d9ee;
            border-radius: 10px;
            padding: 8px 10px;
            font-family: inherit;
        }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #edf2f8; text-align: left; }

        .bar-wrap {
            height: 10px;
            border-radius: 999px;
            background: #edf3fb;
            overflow: hidden;
            min-width: 130px;
        }

        .bar { height: 100%; border-radius: 999px; }
        .bar-quiz { background: linear-gradient(90deg, #36a2eb, #2b7ebd); }
        .bar-materi { background: linear-gradient(90deg, #12b886, #0f8f6a); }

        .legend { display: flex; gap: 16px; flex-wrap: wrap; color: #55718f; font-size: .86rem; margin-top: 8px; }
        .dot { width: 10px; height: 10px; display: inline-block; border-radius: 50%; margin-right: 6px; }

        @media (max-width: 700px) {
            .wrap { padding: 12px; }
            table { min-width: 650px; }
            .table-wrap { overflow-x: auto; }
        }
    </style>
</head>
<body>
<main class="wrap">
    <div class="head">
        <div>
            <h1 style="margin:0;">Detail Progres Mingguan</h1>
            <div style="color:#5a7593;">Siswa: {{ $student->name }} ({{ $student->email }})</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('siswa.dashboard') }}" class="btn btn-alt">Kembali Dashboard</a>
            <form action="{{ route('web.logout') }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" class="btn btn-main" style="cursor:pointer;">Logout</button>
            </form>
        </div>
    </div>

    <section class="card">
        <form method="GET" class="filters">
            <label for="weeks"><strong>Rentang Minggu:</strong></label>
            <select id="weeks" name="weeks">
                <option value="4" {{ (int)$weeks === 4 ? 'selected' : '' }}>4 minggu</option>
                <option value="8" {{ (int)$weeks === 8 ? 'selected' : '' }}>8 minggu</option>
                <option value="12" {{ (int)$weeks === 12 ? 'selected' : '' }}>12 minggu</option>
                <option value="16" {{ (int)$weeks === 16 ? 'selected' : '' }}>16 minggu</option>
            </select>
            <button class="btn btn-main" type="submit" style="cursor:pointer;">Terapkan</button>
        </form>
        <div class="legend">
            <span><span class="dot" style="background:#2b7ebd;"></span>Rata-rata skor quiz</span>
            <span><span class="dot" style="background:#0f8f6a;"></span>Jumlah akses materi</span>
        </div>
    </section>

    <section class="card table-wrap">
        <h2 style="margin:0 0 10px;">Tren Mingguan Quiz dan Materi</h2>
        <table>
            <thead>
                <tr>
                    <th>Minggu</th>
                    <th>Quiz</th>
                    <th>Bar Quiz</th>
                    <th>Akses Materi</th>
                    <th>Bar Materi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($weeklyProgress as $row)
                    <tr>
                        <td>{{ $row['week_label'] }}</td>
                        <td>{{ number_format($row['quiz_avg'], 2) }} ({{ $row['quiz_attempts'] }} attempt)</td>
                        <td>
                            <div class="bar-wrap">
                                <div class="bar bar-quiz" style="width: {{ ($row['quiz_avg'] / $maxQuizAvg) * 100 }}%;"></div>
                            </div>
                        </td>
                        <td>{{ $row['materi_accesses'] }}</td>
                        <td>
                            <div class="bar-wrap">
                                <div class="bar bar-materi" style="width: {{ ($row['materi_accesses'] / $maxMateriAccess) * 100 }}%;"></div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada data progres mingguan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="card table-wrap">
        <h2 style="margin:0 0 10px;">Aktivitas Materi per Kategori</h2>
        <table>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th>Total Akses</th>
                    <th>Materi Unik Diakses</th>
                </tr>
            </thead>
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
    </section>
</main>
</body>
</html>
