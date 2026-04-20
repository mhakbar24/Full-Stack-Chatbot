<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Feedback AI Siswa</title>
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
        .filters{display:flex;gap:8px;flex-wrap:wrap;align-items:end;}
        input{border:1px solid #c7d9ee;border-radius:10px;padding:8px 10px;font-family:inherit;}
        table{width:100%;border-collapse:collapse;} th,td{padding:10px 8px;border-bottom:1px solid #edf2f8;text-align:left;vertical-align:top;}
        .muted{color:#5b7593;font-size:.9rem;}
        .pill{display:inline-block;padding:4px 8px;border-radius:999px;font-size:.75rem;font-weight:700;}
        @media (max-width:700px){.wrap{padding:12px;}table{min-width:720px;}.table-wrap{overflow-x:auto;}}
    </style>
</head>
<body>
<main class="wrap">
    <div class="head">
        <div>
            <h1 style="margin:0;">Riwayat Feedback AI Saya</h1>
            <div class="muted">{{ $student->name }} ({{ $student->email }})</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('siswa.progress.overview') }}" class="btn btn-alt">Kembali Ringkasan</a>
            <a href="{{ route('siswa.dashboard') }}" class="btn btn-main">Dashboard</a>
        </div>
    </div>

    <section class="card">
        <form method="GET" class="filters">
            <div>
                <label>Dari tanggal</label><br>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}">
            </div>
            <div>
                <label>Sampai tanggal</label><br>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}">
            </div>
            <button class="btn btn-main" type="submit">Terapkan</button>
            <a href="{{ route('siswa.history.ai') }}" class="btn btn-alt">Reset</a>
        </form>
    </section>

    <section class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Statistik</th>
                    <th>Ringkasan AI</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($history as $item)
                    <tr>
                        <td>
                            <div>{{ $item->created_at->format('d M Y H:i') }}</div>
                            <span class="muted">#{{ $item->id }}</span>
                        </td>
                        <td>
                            <div>Total quiz: {{ $item->total_quiz }}</div>
                            <div>Rata-rata: {{ number_format($item->average_score, 2) }}</div>
                            <div>Best/Latest: {{ $item->best_score }} / {{ $item->latest_score }}</div>
                        </td>
                        <td>{{ \Illuminate\Support\Str::limit($item->analysis, 180) }}</td>
                        <td>
                            <a href="{{ route('siswa.history.ai.detail', $item->id) }}" class="btn btn-alt">Lihat Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">Belum ada histori feedback AI.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top:12px;">{{ $history->links() }}</div>
    </section>
</main>
</body>
</html>
