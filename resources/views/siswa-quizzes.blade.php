<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Siswa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body{margin:0;font-family:'Space Grotesk',sans-serif;background:#f6fbff;color:#17314b;}
        .wrap{max-width:1080px;margin:0 auto;padding:20px;}
        .head{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;}
        .btn{display:inline-block;text-decoration:none;padding:9px 12px;border-radius:10px;font-weight:700;border:0;cursor:pointer;}
        .btn-main{background:#ff6b35;color:#fff;} .btn-alt{background:#e7f0fb;color:#21476f;}
        .card{margin-top:14px;background:#fff;border:1px solid #dbe8f6;border-radius:14px;padding:14px;}
        .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;}
        .item{border:1px solid #e2ecf8;border-radius:12px;padding:12px;background:#fbfdff;}
        .meta{color:#5f7693;font-size:.86rem;}
        .badge{display:inline-block;padding:3px 8px;border-radius:999px;font-size:.74rem;font-weight:700;background:#e7f6ef;color:#1f684f;}
        input{width:100%;padding:10px;border:1px solid #cbdcf0;border-radius:10px;font:inherit;}
        @media (max-width:720px){.grid{grid-template-columns:1fr;}.wrap{padding:12px;}}
    </style>
</head>
<body>
<main class="wrap">
    <div class="head">
        <div>
            <h1 style="margin:0;">Daftar Quiz</h1>
            <div class="meta">Kerjakan quiz untuk mengukur pemahamanmu.</div>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('siswa.dashboard') }}" class="btn btn-alt">Dashboard</a>
            <a href="{{ route('siswa.materis') }}" class="btn btn-main">Baca Materi</a>
        </div>
    </div>

    <section class="card">
        <div class="meta" style="margin-bottom:8px;">Mode adaptif kamu: <strong>{{ $adaptiveProfile['label'] ?? 'Penguatan Menengah' }}</strong> • {{ $adaptiveProfile['message'] ?? '' }}</div>
        <form method="GET" style="display:grid;grid-template-columns:1fr auto;gap:8px;">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari quiz berdasarkan judul/deskripsi">
            <button type="submit" class="btn btn-main">Cari</button>
        </form>
    </section>

    <section class="card">
        <div class="grid">
            @forelse($quizzes as $quiz)
                @php $latest = $latestResultsByQuiz->get($quiz->id); @endphp
                <article class="item">
                    <strong>{{ $quiz->title }}</strong>
                    <div class="meta">Guru: {{ $quiz->teacher->name ?? '-' }}</div>
                    <div class="meta">Jumlah Soal: {{ $quiz->questions_count }}</div>
                    <div class="meta">{{ \Illuminate\Support\Str::limit($quiz->description, 110) }}</div>
                    <div class="meta" style="margin-top:6px;">Rekomendasi tingkat: <strong>{{ ucfirst($adaptiveProfile['level'] ?? 'menengah') }}</strong></div>
                    @if($latest)
                        <div style="margin-top:8px;"><span class="badge">Skor Terakhir: {{ $latest->score }}</span></div>
                    @endif
                    <a href="{{ route('siswa.quizzes.show', $quiz->id) }}" class="btn btn-alt" style="margin-top:10px;">Kerjakan Quiz</a>
                </article>
            @empty
                <div class="item">Belum ada quiz tersedia.</div>
            @endforelse
        </div>
        <div style="margin-top:12px;">{{ $quizzes->links() }}</div>
    </section>
</main>
</body>
</html>
