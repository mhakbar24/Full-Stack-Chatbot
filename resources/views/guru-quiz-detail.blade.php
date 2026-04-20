<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Soal Quiz</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body{margin:0;background:#f7fbff;color:#173253;font-family:'Space Grotesk',sans-serif;}
        .wrap{max-width:1120px;margin:0 auto;padding:22px;}
        .card{background:#fff;border:1px solid #dbe6f6;border-radius:14px;padding:16px;margin-top:14px;}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
        input,textarea,select{width:100%;padding:10px;border:1px solid #c8d8ee;border-radius:10px;font:inherit;}
        button,.btn{border:0;border-radius:10px;padding:10px 12px;background:#ff6b35;color:#fff;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;}
        .btn-alt{background:#2d4f78;}
        .question{border:1px solid #e4edf9;border-radius:12px;padding:12px;margin-top:10px;}
        .actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
        @media (max-width:800px){.grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<div class="wrap">
    <h1 style="margin:0;">Kelola Soal: {{ $quiz->title }}</h1>
    <div class="actions" style="margin-top:12px;">
        <a class="btn btn-alt" href="{{ route('guru.quizzes') }}">Kembali ke Quiz</a>
        <a class="btn btn-alt" href="{{ route('guru.dashboard') }}">Dashboard</a>
    </div>

    <div class="card">
        @if (session('status'))
            <div style="color:#0b7a58;font-weight:700;">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div style="color:#b0233f;font-weight:700;">{{ $errors->first() }}</div>
        @endif

        <h3>Edit Quiz</h3>
        <form method="POST" action="{{ route('guru.quizzes.update', $quiz->id) }}" style="display:grid;gap:10px;">
            @csrf @method('PUT')
            <input name="title" value="{{ $quiz->title }}" required>
            <textarea name="description" rows="3">{{ $quiz->description }}</textarea>
            <div><button type="submit">Update Quiz</button></div>
        </form>
    </div>

    <div class="card">
        <h3>Tambah Soal Baru</h3>
        <form method="POST" action="{{ route('guru.quizzes.questions.store', $quiz->id) }}" style="display:grid;gap:10px;">
            @csrf
            <input name="question_text" placeholder="Pertanyaan" required>
            <div class="grid">
                <input name="option_a" placeholder="Opsi A" required>
                <input name="option_b" placeholder="Opsi B" required>
                <input name="option_c" placeholder="Opsi C" required>
                <input name="option_d" placeholder="Opsi D" required>
            </div>
            <div style="max-width:180px;">
                <select name="correct_answer" required>
                    <option value="A">Jawaban benar: A</option>
                    <option value="B">Jawaban benar: B</option>
                    <option value="C">Jawaban benar: C</option>
                    <option value="D">Jawaban benar: D</option>
                </select>
            </div>
            <div class="grid">
                <div>
                    <select name="difficulty_level">
                        <option value="dasar">Tingkat Soal: Dasar</option>
                        <option value="menengah" selected>Tingkat Soal: Menengah</option>
                        <option value="lanjut">Tingkat Soal: Lanjut</option>
                    </select>
                </div>
                <div>
                    <select name="competency_key">
                        <option value="">Kompetensi: Umum</option>
                        @foreach(($competencyOptions ?? []) as $option)
                            <option value="{{ $option['key'] }}">Kompetensi: {{ $option['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div><button type="submit">Tambah Soal</button></div>
        </form>
    </div>

    <div class="card">
        <h3>Daftar Soal</h3>
        @forelse ($quiz->questions as $question)
            <div class="question">
                <form method="POST" action="{{ route('guru.questions.update', $question->id) }}" style="display:grid;gap:8px;">
                    @csrf @method('PUT')
                    <input name="question_text" value="{{ $question->question_text }}" required>
                    <div style="color:#5f7594;font-size:.86rem;">Metadata: {{ ucfirst($question->difficulty_level ?? 'menengah') }} • {{ $question->competency_key ?: 'umum' }}</div>
                    <div class="grid">
                        <input name="option_a" value="{{ $question->option_a }}" required>
                        <input name="option_b" value="{{ $question->option_b }}" required>
                        <input name="option_c" value="{{ $question->option_c }}" required>
                        <input name="option_d" value="{{ $question->option_d }}" required>
                    </div>
                    <div class="grid">
                        <div style="max-width:220px;">
                            <select name="correct_answer" required>
                                <option value="A" @selected($question->correct_answer === 'A')>Jawaban benar: A</option>
                                <option value="B" @selected($question->correct_answer === 'B')>Jawaban benar: B</option>
                                <option value="C" @selected($question->correct_answer === 'C')>Jawaban benar: C</option>
                                <option value="D" @selected($question->correct_answer === 'D')>Jawaban benar: D</option>
                            </select>
                        </div>
                        <div>
                            <select name="difficulty_level">
                                <option value="dasar" @selected(($question->difficulty_level ?? 'menengah') === 'dasar')>Tingkat: Dasar</option>
                                <option value="menengah" @selected(($question->difficulty_level ?? 'menengah') === 'menengah')>Tingkat: Menengah</option>
                                <option value="lanjut" @selected(($question->difficulty_level ?? 'menengah') === 'lanjut')>Tingkat: Lanjut</option>
                            </select>
                        </div>
                        <div>
                            <select name="competency_key">
                                <option value="">Kompetensi: Umum</option>
                                @foreach(($competencyOptions ?? []) as $option)
                                    <option value="{{ $option['key'] }}" @selected(($question->competency_key ?? '') === $option['key'])>Kompetensi: {{ $option['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="actions">
                        <button type="submit">Simpan Perubahan</button>
                </form>
                        <form method="POST" action="{{ route('guru.questions.destroy', $question->id) }}" onsubmit="return confirm('Hapus soal ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" style="background:#b0233f;">Hapus</button>
                        </form>
                    </div>
            </div>
        @empty
            <div>Belum ada soal di quiz ini.</div>
        @endforelse
    </div>
</div>
</body>
</html>
