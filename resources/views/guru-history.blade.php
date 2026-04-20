<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histori AI Guru</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { margin:0; font-family:'Space Grotesk',sans-serif; background:#f5f8ff; color:#163150; }
        .wrap { max-width:1100px; margin:0 auto; padding:22px; }
        .top { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; }
        .card { background:#fff; border:1px solid #dde7f5; border-radius:14px; padding:16px; margin-top:14px; }
        .filters { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
        input, select { width:100%; border:1px solid #c7d8ee; border-radius:10px; padding:10px; font:inherit; }
        button, .btn { border:0; border-radius:10px; padding:10px 12px; background:#ff6b35; color:#fff; font-weight:700; cursor:pointer; text-decoration:none; display:inline-block; }
        table { width:100%; border-collapse: collapse; }
        th, td { text-align:left; padding:10px 8px; border-bottom:1px solid #edf2fa; vertical-align:top; }
        .muted { color:#5b7190; font-size:.9rem; }
        .actions { display:flex; gap:8px; align-items:center; }
        .pill { background:#e7f1ff; color:#2b4f7e; border-radius:999px; padding:4px 8px; font-size:.75rem; }
        .link { color:#1f4d80; font-weight:700; text-decoration:none; }
        @media (max-width:900px){ .filters{ grid-template-columns:1fr 1fr; } }
        @media (max-width:600px){ .filters{ grid-template-columns:1fr; } table,thead,tbody,tr,th,td{ display:block;} th{display:none;} td{padding:8px 0;} }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <div>
            <h1 style="margin:0;">Histori Analisis AI Siswa</h1>
            <div class="muted">Login sebagai {{ $user->name }} ({{ strtoupper($user->role) }})</div>
        </div>
        <div class="actions">
            <a href="{{ route('guru.dashboard') }}" class="btn" style="background:#2d4f78;">Kembali Dashboard</a>
            <form action="{{ route('web.logout') }}" method="POST">@csrf<button type="submit" style="background:#1f3a5c;">Logout</button></form>
        </div>
    </div>

    <div class="card">
        <form method="GET" class="filters">
            <div>
                <label>Dari tanggal</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}">
            </div>
            <div>
                <label>Sampai tanggal</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}">
            </div>
            <div>
                <label>Siswa</label>
                <select name="student_id">
                    <option value="">Semua siswa</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected(($filters['student_id'] ?? null) == $student->id)>{{ $student->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex; align-items:end; gap:8px;">
                <button type="submit">Terapkan Filter</button>
                <a class="btn" style="background:#8ba4c5;" href="{{ route('guru.history') }}">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Siswa</th>
                    <th>Statistik</th>
                    <th>Ringkasan AI</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($history as $item)
                <tr>
                    <td>
                        <div>{{ $item->created_at->format('d M Y H:i') }}</div>
                        <span class="pill">#{{ $item->id }}</span>
                        <div><a class="link" href="{{ route('guru.history.detail', $item->id) }}">Lihat detail</a></div>
                    </td>
                    <td>
                        <div><strong>{{ $item->student->name ?? 'Unknown' }}</strong></div>
                        <div class="muted">{{ $item->student->email ?? '-' }}</div>
                    </td>
                    <td>
                        <div>Total quiz: {{ $item->total_quiz }}</div>
                        <div>Rata-rata: {{ number_format($item->average_score, 2) }}</div>
                        <div>Best/Latest: {{ $item->best_score }} / {{ $item->latest_score }}</div>
                    </td>
                    <td>{{ \Illuminate\Support\Str::limit($item->analysis, 220) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Belum ada histori analisis untuk filter ini.</td></tr>
            @endforelse
            </tbody>
        </table>

        <div style="margin-top:12px;">{{ $history->links() }}</div>
    </div>
</div>
</body>
</html>
