<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materi Siswa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body{margin:0;font-family:'Space Grotesk',sans-serif;background:#f6fbff;color:#17314b;}
        .wrap{max-width:1100px;margin:0 auto;padding:20px;}
        .head{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;}
        .btn{display:inline-block;text-decoration:none;padding:9px 12px;border-radius:10px;font-weight:700;border:0;cursor:pointer;}
        .btn-main{background:#ff6b35;color:#fff;} .btn-alt{background:#e7f0fb;color:#21476f;}
        .card{margin-top:14px;background:#fff;border:1px solid #dbe8f6;border-radius:14px;padding:14px;}
        .filters{display:grid;grid-template-columns:2fr 1fr auto;gap:8px;}
        input,select{width:100%;padding:10px;border:1px solid #cbdcf0;border-radius:10px;font:inherit;}
        .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;}
        .item{border:1px solid #e2ecf8;border-radius:12px;padding:12px;background:#fbfdff;}
        .meta{color:#5f7693;font-size:.86rem;}
        .badge{display:inline-block;padding:3px 8px;border-radius:999px;font-size:.74rem;font-weight:700;background:#fef3c7;color:#92400e;}
        .done{background:#dcfce7;color:#166534;}
        @media (max-width:900px){.grid{grid-template-columns:1fr 1fr;}.filters{grid-template-columns:1fr;}}
        @media (max-width:560px){.grid{grid-template-columns:1fr;}.wrap{padding:12px;}}
    </style>
</head>
<body>
<main class="wrap">
    <div class="head">
        <div>
            <h1 style="margin:0;">Daftar Materi</h1>
            <div class="meta">Pilih materi untuk dibaca. Aktivitasmu akan tercatat sebagai progres belajar.</div>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('siswa.dashboard') }}" class="btn btn-alt">Dashboard</a>
            <a href="{{ route('siswa.quizzes') }}" class="btn btn-main">Kerjakan Quiz</a>
        </div>
    </div>

    <section class="card">
        <form method="GET" class="filters">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari judul/deskripsi/kategori materi">
            <select name="category">
                <option value="">Semua Kategori</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}" {{ ($filters['category'] ?? '') === $category ? 'selected' : '' }}>{{ $category }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-main">Filter</button>
        </form>
    </section>

    <section class="card">
        <div class="grid">
            @forelse($materis as $materi)
                <article class="item">
                    <div style="display:flex;justify-content:space-between;gap:8px;align-items:center;">
                        <strong>{{ $materi->title }}</strong>
                        @if($accessedIds->contains($materi->id))
                            <span class="badge done">Sudah Dibaca</span>
                        @else
                            <span class="badge">Belum Dibaca</span>
                        @endif
                    </div>
                    <div class="meta" style="margin-top:6px;">Kategori: {{ $materi->category ?: 'Umum' }}</div>
                    <div class="meta">Guru: {{ $materi->teacher->name ?? '-' }}</div>
                    <div class="meta">{{ \Illuminate\Support\Str::limit($materi->description, 110) }}</div>
                    <a href="{{ route('siswa.materis.show', $materi->id) }}" class="btn btn-alt" style="margin-top:10px;">Baca Materi</a>
                </article>
            @empty
                <div class="item">Belum ada materi tersedia.</div>
            @endforelse
        </div>
        <div style="margin-top:12px;">{{ $materis->links() }}</div>
    </section>
</main>
</body>
</html>
