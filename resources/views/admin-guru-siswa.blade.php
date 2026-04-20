<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Daftar Guru dan Siswa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; font-family: 'Manrope', sans-serif; background: #0f172a; color: #12304a; }
        .wrap { max-width: 1180px; margin: 0 auto; padding: 20px; }
        .head { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; color: #dff3ff; }
        .card { margin-top: 14px; background: #fff; border-radius: 14px; border: 1px solid #d8e4f2; padding: 16px; }
        .actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .btn { border: 0; border-radius: 10px; padding: 10px 12px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-main { background: #ff7a3b; color: #fff; }
        .btn-alt { background: #2d4f78; color: #fff; }
        input { width: 100%; max-width: 420px; padding: 10px; border: 1px solid #c7d8ee; border-radius: 10px; font: inherit; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 8px; text-align: left; border-bottom: 1px solid #edf2fa; font-size: .92rem; vertical-align: top; }
        .muted { color: #5d7393; font-size: .88rem; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .kpi { display: inline-block; padding: 4px 8px; border-radius: 999px; font-size: .75rem; font-weight: 700; background: #e7f1ff; color: #2b4f7e; }
        @media (max-width: 960px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="head">
        <div>
            <h1 style="margin:0;">Daftar Guru dan Siswa</h1>
            <div class="muted" style="color:#dff3ff;">Login sebagai {{ $user->name }} ({{ strtoupper($user->role) }})</div>
        </div>
        <div class="actions">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-alt">Kembali Dashboard</a>
            <a href="{{ route('admin.users') }}" class="btn btn-main">Kelola User</a>
            <form action="{{ route('web.logout') }}" method="POST">@csrf<button type="submit" class="btn" style="background:#7ee4c5;color:#10334f;">Logout</button></form>
        </div>
    </div>

    <div class="card">
        <form method="GET" class="actions">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama/email guru atau siswa">
            <button type="submit" class="btn btn-main">Cari</button>
            <a href="{{ route('admin.guru-siswa') }}" class="btn" style="background:#8aa6c5;color:#fff;">Reset</a>
        </form>
    </div>

    <div class="grid">
        <section class="card">
            <h3 style="margin-top:0;">Daftar Guru</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Quiz</th>
                        <th>Materi</th>
                        <th>Analisis AI</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($teachers as $teacher)
                    <tr>
                        <td>{{ $teacher->name }}</td>
                        <td>{{ $teacher->email }}</td>
                        <td><span class="kpi">{{ $teacher->quizzes_count }}</span></td>
                        <td><span class="kpi">{{ $teacher->materis_count }}</span></td>
                        <td><span class="kpi">{{ $teacher->progress_analyses_count }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5">Data guru tidak ditemukan.</td></tr>
                @endforelse
                </tbody>
            </table>
            <div style="margin-top:12px;">{{ $teachers->links() }}</div>
        </section>

        <section class="card">
            <h3 style="margin-top:0;">Daftar Siswa</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Attempt Quiz</th>
                        <th>Rata-rata</th>
                        <th>Akses Materi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($students as $student)
                    <tr>
                        <td>{{ $student->name }}</td>
                        <td>{{ $student->email }}</td>
                        <td><span class="kpi">{{ $student->total_quiz_attempts }}</span></td>
                        <td><span class="kpi">{{ number_format($student->avg_quiz_score, 2) }}</span></td>
                        <td><span class="kpi">{{ $student->total_materi_accesses }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5">Data siswa tidak ditemukan.</td></tr>
                @endforelse
                </tbody>
            </table>
            <div style="margin-top:12px;">{{ $students->links() }}</div>
        </section>
    </div>
</div>
</body>
</html>
