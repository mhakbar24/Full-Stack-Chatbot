<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kerjakan Quiz</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body{margin:0;font-family:'Space Grotesk',sans-serif;background:#f6fbff;color:#17314b;}
        .wrap{max-width:980px;margin:0 auto;padding:20px;}
        .btn{display:inline-block;text-decoration:none;padding:9px 12px;border-radius:10px;font-weight:700;border:0;cursor:pointer;}
        .btn-main{background:#ff6b35;color:#fff;} .btn-alt{background:#e7f0fb;color:#21476f;}
        .card{margin-top:14px;background:#fff;border:1px solid #dbe8f6;border-radius:14px;padding:16px;}
        .meta{color:#5f7693;font-size:.9rem;}
        .q{border:1px solid #e2ecf8;border-radius:12px;padding:12px;background:#fbfdff;margin-bottom:10px;}
        .opts label{display:block;margin:6px 0;cursor:pointer;}
        @media (max-width:560px){.wrap{padding:12px;}}
    </style>
</head>
<body>
<main class="wrap">
    <a href="{{ route('siswa.quizzes') }}" class="btn btn-alt">Kembali ke Daftar Quiz</a>

    @if (session('status'))
        <section class="card" style="border-color:#d8eadf;background:#f5fff8;">
            <strong style="color:#17653e;">{{ session('status') }}</strong>
        </section>
    @endif

    <section class="card">
        <h1 style="margin:0;">{{ $quiz->title }}</h1>
        <div class="meta">Guru: {{ $quiz->teacher->name ?? '-' }} • Jumlah soal: {{ $quiz->questions->count() }}</div>
        <div class="meta" style="margin-top:6px;">{{ $quiz->description ?: 'Tidak ada deskripsi quiz.' }}</div>
        <div class="meta" style="margin-top:6px;">Mode adaptif: <strong>{{ $adaptiveProfile['label'] ?? 'Menengah' }}</strong> • {{ $adaptiveProfile['message'] ?? '' }}</div>
        <div class="meta" style="margin-top:6px;">Fokus kompetensi saat ini: <strong>{{ $focusCompetencyName ?? 'Umum' }}</strong> • Urutan soal diprioritaskan sesuai profil belajar kamu.</div>

        @if($latestResult)
            <div style="margin-top:8px;" class="meta">Skor terakhir kamu: <strong>{{ $latestResult->score }}</strong></div>
        @endif
    </section>

    <section class="card">
        <form method="POST" action="{{ route('siswa.quizzes.submit', $quiz->id) }}">
            @csrf
            @forelse($quiz->questions as $index => $question)
                <div class="q">
                    <strong>{{ $index + 1 }}. {{ $question->question_text }}</strong>
                    <div class="meta" style="margin-top:5px;">Tingkat: <strong>{{ ucfirst($question->difficulty_level ?? 'menengah') }}</strong> • Kompetensi: <strong>{{ $question->competency_key ?: 'umum' }}</strong></div>
                    @if(!empty($questionHints[(string) $question->id]))
                        <div style="margin-top:8px;padding:8px 10px;border-radius:9px;background:#eefcf8;border:1px solid #d2f0e7;white-space:pre-line;">{{ $questionHints[(string) $question->id] }}</div>
                    @endif
                    <div class="opts">
                        <label><input type="radio" name="answers[{{ $question->id }}]" value="A" required> A. {{ $question->option_a }}</label>
                        <label><input type="radio" name="answers[{{ $question->id }}]" value="B"> B. {{ $question->option_b }}</label>
                        <label><input type="radio" name="answers[{{ $question->id }}]" value="C"> C. {{ $question->option_c }}</label>
                        <label><input type="radio" name="answers[{{ $question->id }}]" value="D"> D. {{ $question->option_d }}</label>
                    </div>
                    <div style="margin-top:8px;">
                        <button
                            type="submit"
                            class="btn btn-alt"
                            style="padding:6px 10px;"
                            formaction="{{ route('siswa.quizzes.questions.hint', ['quizId' => $quiz->id, 'questionId' => $question->id]) }}"
                            formmethod="POST"
                            formnovalidate
                            onclick="this.form.querySelectorAll('input[type=radio]').forEach(function(el){ el.required=false; });"
                        >Minta Hint</button>
                    </div>
                </div>
            @empty
                <div class="q">Quiz ini belum memiliki soal.</div>
            @endforelse

            @if($quiz->questions->count() > 0)
                <button type="submit" class="btn btn-main">Kirim Jawaban</button>
            @endif
        </form>
    </section>
</main>
</body>
</html>
