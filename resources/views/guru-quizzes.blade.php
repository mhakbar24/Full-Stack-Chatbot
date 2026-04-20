<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Quiz Guru</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body{margin:0;background:#f7fbff;color:#173253;font-family:'Space Grotesk',sans-serif;}
        .wrap{max-width:1120px;margin:0 auto;padding:22px;}
        .card{background:#fff;border:1px solid #dbe6f6;border-radius:14px;padding:16px;margin-top:14px;}
        .actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
        .filters{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:10px;align-items:end;}
        .btn,button{border:0;border-radius:10px;padding:10px 12px;background:#ff6b35;color:#fff;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;}
        .btn-alt{background:#2d4f78;}
        input,textarea,select{width:100%;padding:10px;border:1px solid #c8d8ee;border-radius:10px;font:inherit;}
        table{width:100%;border-collapse:collapse;}th,td{padding:10px 8px;text-align:left;border-bottom:1px solid #edf2fa;vertical-align:top;}
        .muted{color:#5f7594;font-size:.9rem;}
        @media (max-width:860px){ .filters{grid-template-columns:1fr 1fr;} }
        @media (max-width:700px){table,thead,tbody,tr,th,td{display:block;}th{display:none;}td{padding:7px 0;}}
    </style>
</head>
<body>
<div class="wrap">
    <h1 style="margin:0;">Manajemen Quiz Guru</h1>
    <div class="muted">Login sebagai {{ $user->name }} ({{ strtoupper($user->role) }})</div>

    <div class="actions" style="margin-top:12px;">
        <a class="btn btn-alt" href="{{ route('guru.dashboard') }}">Kembali Dashboard</a>
        <a class="btn btn-alt" href="{{ route('guru.materis') }}">Kelola Materi</a>
        <form action="{{ route('web.logout') }}" method="POST">@csrf<button type="submit" style="background:#1f3a5c;">Logout</button></form>
    </div>

    <div class="card">
        <h3>Filter Quiz</h3>
        <form method="GET" class="filters" style="margin-bottom:8px;">
            <div>
                <label>Cari Judul</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Contoh: Pemrograman Web Dasar">
            </div>
            <div>
                <label>Dari</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}">
            </div>
            <div>
                <label>Sampai</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}">
            </div>
            <div class="actions">
                <button type="submit" class="btn-alt">Terapkan</button>
                <a class="btn" style="background:#8ba4c5;" href="{{ route('guru.quizzes') }}">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        @if (session('status'))
            <div style="color:#0b7a58;font-weight:700;">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div style="color:#b0233f;font-weight:700;">{{ $errors->first() }}</div>
        @endif

        <h3>Tambah Quiz</h3>
        <form method="POST" action="{{ route('guru.quizzes.store') }}" style="display:grid;gap:10px;">
            @csrf
            <div>
                <label>Judul</label>
                <input name="title" required>
            </div>
            <div>
                <label>Deskripsi</label>
                <textarea name="description" rows="3"></textarea>
            </div>
            <div><button type="submit">Simpan Quiz</button></div>
        </form>

        <hr style="border:none;border-top:1px solid #e6eef8;margin:16px 0;">

        <h3>Generate Quiz + Soal dengan AI (CP/ATP Fase F)</h3>
        <form method="POST" action="{{ route('guru.quizzes.generate-ai') }}" style="display:grid;gap:10px;grid-template-columns:2fr 1fr 1fr 1fr;align-items:end;">
            @csrf
            <div>
                <label>Topik Quiz</label>
                <input name="topic" required placeholder="Contoh: HTML semantik dan form validasi">
            </div>
            <div>
                <label>Kompetensi CP/ATP</label>
                <select name="competency_key">
                    <option value="">Auto (biarkan AI pilih)</option>
                    @foreach(($competencyOptions ?? []) as $option)
                        <option value="{{ $option['key'] }}">{{ $option['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Jumlah Soal</label>
                <input type="number" name="question_count" min="5" max="20" value="10">
            </div>
            <div>
                <label>Kesulitan</label>
                <select name="difficulty">
                    <option value="dasar">Dasar</option>
                    <option value="menengah" selected>Menengah</option>
                    <option value="lanjut">Lanjut</option>
                </select>
            </div>
            <div style="grid-column:1 / -1;">
                <button type="submit">Generate Quiz AI</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Daftar Quiz Saya</h3>
        <table>
            <thead>
                <tr><th>Judul</th><th>Deskripsi</th><th>Soal</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            @forelse($quizzes as $quiz)
                <tr>
                    <td><strong>{{ $quiz->title }}</strong></td>
                    <td>{{ $quiz->description ?: '-' }}</td>
                    <td>{{ $quiz->questions_count }}</td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-alt" href="{{ route('guru.quizzes.show', $quiz->id) }}">Kelola Soal</a>
                            <form method="POST" action="{{ route('guru.quizzes.destroy', $quiz->id) }}" onsubmit="return confirm('Hapus quiz ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:#b0233f;">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">Belum ada quiz.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:12px;">{{ $quizzes->links() }}</div>
    </div>
</div>
</body>
</html>
