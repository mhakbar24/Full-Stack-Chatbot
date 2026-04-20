<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Quiz</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body{margin:0;font-family:'Space Grotesk',sans-serif;background:#f6fbff;color:#17314b;}
        .wrap{max-width:760px;margin:0 auto;padding:20px;}
        .card{margin-top:14px;background:#fff;border:1px solid #dbe8f6;border-radius:14px;padding:18px;text-align:center;}
        .score{font-size:2.2rem;font-weight:700;margin-top:8px;}
        .meta{color:#5f7693;font-size:.92rem;}
        .btn{display:inline-block;text-decoration:none;padding:10px 13px;border-radius:10px;font-weight:700;margin:6px;}
        .btn-main{background:#ff6b35;color:#fff;} .btn-alt{background:#e7f0fb;color:#21476f;}
        .good{color:#17653e;} .mid{color:#92400e;} .bad{color:#991b1b;}
    </style>
</head>
<body>
<main class="wrap">
    <section class="card">
        <h1 style="margin:0;">Hasil Quiz: {{ $quiz->title }}</h1>
        <div class="meta">Dikerjakan pada {{ $result->created_at->format('d M Y H:i') }}</div>
        <div class="score">{{ $result->score }} / {{ $totalQuestions }}</div>
        <div class="meta">Nilai: {{ number_format($percent, 2) }}%</div>

        @if($percent >= 80)
            <p class="good"><strong>Kerja bagus! Pemahamanmu sangat baik.</strong></p>
        @elseif($percent >= 60)
            <p class="mid"><strong>Lumayan! Ulangi materi agar lebih mantap.</strong></p>
        @else
            <p class="bad"><strong>Terus semangat. Baca materi lagi lalu coba ulang.</strong></p>
        @endif

        <div style="margin-top:10px;">
            <a href="{{ route('siswa.quizzes.show', $quiz->id) }}" class="btn btn-main">Coba Lagi</a>
            <a href="{{ route('siswa.materis') }}" class="btn btn-alt">Baca Materi</a>
            <a href="{{ route('siswa.dashboard') }}" class="btn btn-alt">Dashboard</a>
        </div>
    </section>
</main>
</body>
</html>
