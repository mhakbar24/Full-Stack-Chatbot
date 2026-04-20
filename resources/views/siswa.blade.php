<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa | Quiz AI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Bricolage+Grotesque:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #14304a;
            --paper: #f6fbff;
            --line: #d7e5f3;
            --card: #ffffff;
            --accent: #ff6b35;
            --teal: #0e9384;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Space Grotesk', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 8% 6%, #ffe5bf 0, transparent 28%),
                radial-gradient(circle at 92% 90%, #c9f5ea 0, transparent 30%),
                linear-gradient(130deg, #f6fbff 0%, #eef6ff 42%, #fff7eb 100%);
            min-height: 100vh;
        }

        .wrap { max-width: 1120px; margin: 0 auto; padding: 22px; }
        .top { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }

        .logout-btn {
            border: 0;
            border-radius: 10px;
            padding: 8px 12px;
            background: #1f3a5c;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }

        .hero {
            margin-top: 10px;
            background: linear-gradient(135deg, #103454, #1f5f7d 68%, #268a9b);
            color: #fff;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 18px 38px rgba(10, 34, 56, 0.22);
        }

        .hero h1 {
            margin: 0;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: clamp(1.5rem, 4.2vw, 2.2rem);
            line-height: 1.15;
        }

        .hero p { margin: 10px 0 0; color: #e7f7ff; max-width: 680px; }

        .grid {
            margin-top: 16px;
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }

        .stat {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 14px;
        }

        .stat .label { font-size: .82rem; color: #57708f; }
        .stat .value { margin-top: 5px; font-size: 1.34rem; font-weight: 700; }

        .section {
            margin-top: 16px;
            display: grid;
            gap: 14px;
            grid-template-columns: 1fr 1fr;
        }

        .panel {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px;
        }

        .chat-list {
            max-height: 360px;
            overflow: auto;
            display: grid;
            gap: 10px;
            margin-bottom: 10px;
        }

        .chat-bubble {
            border-radius: 12px;
            padding: 10px 12px;
            border: 1px solid #dce9f7;
            background: #f8fbff;
        }

        .chat-bubble.bot {
            background: #effcf8;
            border-color: #caefe4;
        }

        .chat-label {
            font-size: .76rem;
            font-weight: 700;
            color: #4d6783;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .panel h2 { margin: 0 0 12px; font-size: 1.03rem; }

        .item {
            padding: 10px;
            border-radius: 11px;
            background: #f8fbff;
            border: 1px solid #e3eef9;
            margin-bottom: 9px;
        }

        .item strong { display: block; margin-bottom: 4px; }
        .meta { color: #607991; font-size: .85rem; }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: .74rem;
            font-weight: 700;
            margin-left: 6px;
            background: #e6f4ff;
            color: #23547d;
        }

        .badge-good { background: #dcfce7; color: #166534; }
        .badge-warn { background: #fef3c7; color: #92400e; }

        @media (max-width: 980px) {
            .grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .section { grid-template-columns: 1fr; }
        }

        @media (max-width: 560px) {
            .wrap { padding: 14px; }
            .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .hero { padding: 16px; }
        }
    </style>
</head>
<body>
<main class="wrap">
    <div class="top">
        <div>Masuk sebagai <strong>{{ $user->name }}</strong> ({{ strtoupper($user->role) }})</div>
        <form action="{{ route('web.logout') }}" method="POST">
            @csrf
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>

    <section class="hero">
        <h1>Halo {{ $student->name }}, lanjutkan progres belajarmu</h1>
        <p>Dashboard ini menampilkan ringkasan performa quiz dan aktivitas materi agar kamu bisa fokus pada topik yang belum dikuasai.</p>
        <div style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap;">
            <a href="{{ route('siswa.quizzes') }}" style="display:inline-block;text-decoration:none;background:#ff6b35;color:#fff;padding:9px 12px;border-radius:10px;font-weight:700;">Kerjakan Quiz</a>
            <a href="{{ route('siswa.materis') }}" style="display:inline-block;text-decoration:none;background:#0e9384;color:#fff;padding:9px 12px;border-radius:10px;font-weight:700;">Baca Materi</a>
            <a href="{{ route('siswa.progress') }}" style="display:inline-block;text-decoration:none;background:#e7f1ff;color:#22466e;padding:9px 12px;border-radius:10px;font-weight:700;">Lihat Progres</a>
            <a href="{{ route('siswa.progress.overview') }}" style="display:inline-block;text-decoration:none;background:#1f3a5c;color:#fff;padding:9px 12px;border-radius:10px;font-weight:700;">Ringkasan Progres</a>
        </div>
    </section>

    <section class="grid">
        <article class="stat"><div class="label">Quiz Dikerjakan</div><div class="value">{{ $stats['total_attempts'] }}</div></article>
        <article class="stat"><div class="label">Rata-rata Skor</div><div class="value">{{ number_format($stats['average_score'], 2) }}</div></article>
        <article class="stat"><div class="label">Skor Terbaik</div><div class="value">{{ $stats['best_score'] }}</div></article>
        <article class="stat"><div class="label">Skor Terbaru</div><div class="value">{{ $stats['latest_score'] }}</div></article>
        <article class="stat"><div class="label">Materi Diakses</div><div class="value">{{ $stats['materi_accessed'] }}</div></article>
        <article class="stat"><div class="label">Cakupan Materi</div><div class="value">{{ number_format($stats['materi_coverage'], 2) }}%</div></article>
    </section>

    <section class="section">
        <article class="panel">
            <h2>Riwayat Quiz Terbaru</h2>
            @forelse($recentQuizResults as $quiz)
                <div class="item">
                    <strong>{{ $quiz['quiz_title'] }}</strong>
                    <div class="meta">
                        Skor: <strong>{{ $quiz['score'] }}</strong>
                        <span class="badge {{ $quiz['score'] >= 75 ? 'badge-good' : 'badge-warn' }}">
                            {{ $quiz['score'] >= 75 ? 'Bagus' : 'Perlu Latihan' }}
                        </span>
                    </div>
                    <div class="meta">{{ $quiz['taken_at']->format('d M Y H:i') }}</div>
                </div>
            @empty
                <div class="item"><strong>Belum ada hasil quiz</strong><div class="meta">Mulai kerjakan quiz agar progres tampil di sini.</div></div>
            @endforelse
        </article>

        <article class="panel">
            <h2>Aktivitas Materi Terbaru</h2>
            @forelse($recentMateriAccess as $materi)
                <div class="item">
                    <strong>{{ $materi['materi_title'] }}</strong>
                    <div class="meta">Kategori: {{ $materi['category'] ?: 'Umum' }}</div>
                    <div class="meta">Akses: {{ $materi['accessed_at']->format('d M Y H:i') }}</div>
                </div>
            @empty
                <div class="item"><strong>Belum ada aktivitas materi</strong><div class="meta">Buka materi agar aktivitasmu tercatat.</div></div>
            @endforelse
        </article>
    </section>

    <section class="panel" style="margin-top:14px;">
        <h2>Rekomendasi Materi Berikutnya</h2>
        <div class="meta" style="margin-bottom:8px;">Mode adaptif saat ini: <strong>{{ $adaptiveProfile['label'] ?? 'Penguatan Menengah' }}</strong> • {{ $adaptiveProfile['message'] ?? '' }}</div>
        <div style="margin-bottom:10px;">
            <a href="{{ route('siswa.progress') }}" style="display:inline-block;text-decoration:none;background:#ff6b35;color:#fff;padding:9px 12px;border-radius:10px;font-weight:700;">Lihat Detail Progres Mingguan</a>
        </div>
        @if($recommendedMateri->isEmpty())
            <div class="item"><strong>Semua materi sudah kamu akses</strong><div class="meta">Pertahankan konsistensi belajarmu.</div></div>
        @else
            @foreach($recommendedMateri as $materi)
                <div class="item">
                    <strong>{{ $materi->title }}</strong>
                    <div class="meta">Kategori: {{ $materi->category ?: 'Umum' }} • Dibuat {{ $materi->created_at->format('d M Y') }}</div>
                </div>
            @endforeach
        @endif
    </section>

    <section class="panel" style="margin-top:14px;">
        <h2>Learning Path Otomatis 7 Hari</h2>
        <div class="meta" style="margin-bottom:8px;">Rencana belajar ini dibentuk dari performa quiz, materi yang dibaca, dan peta CP/ATP kamu.</div>
        @foreach(($learningPath ?? []) as $step)
            <div class="item">
                <strong>{{ $step['day'] }}</strong>
                <div class="meta">{{ $step['task'] }}</div>
            </div>
        @endforeach
    </section>

    <section class="panel" style="margin-top:14px;">
        <h2>Chatbot Belajar Siswa</h2>
        <div class="meta" style="margin-bottom:10px;">Chatbot ini otomatis memakai konteks quiz yang pernah kamu kerjakan dan materi yang sudah kamu baca.</div>

        @if (session('chatbot_success'))
            <div style="margin-bottom:10px;color:#166534;font-weight:700;">{{ session('chatbot_success') }}</div>
        @endif
        @if (session('chatbot_error'))
            <div style="margin-bottom:10px;color:#b42318;font-weight:700;">{{ session('chatbot_error') }}</div>
        @endif
        @if ($errors->has('message'))
            <div style="margin-bottom:10px;color:#b42318;font-weight:700;">{{ $errors->first('message') }}</div>
        @endif

        <div class="chat-list">
            @forelse(($chatbotHistory ?? []) as $chat)
                <div class="chat-bubble">
                    <div class="chat-label">Siswa</div>
                    <div>{{ $chat['question'] }}</div>
                </div>
                <div class="chat-bubble bot">
                    <div class="chat-label">Chatbot</div>
                    <div style="white-space:pre-line;">{{ $chat['answer'] }}</div>
                    @if(!empty($chat['citations']) && is_array($chat['citations']))
                        <div class="meta" style="margin-top:5px;">
                            <strong>Sumber:</strong>
                            @foreach($chat['citations'] as $ref)
                                <a href="{{ $ref['url'] ?? '#' }}" style="color:#1f4f79;font-weight:700;text-decoration:none;">{{ $ref['title'] ?? 'Materi' }}</a>@if(!$loop->last), @endif
                            @endforeach
                        </div>
                    @endif
                    <div class="meta" style="margin-top:6px;">{{ \Illuminate\Support\Carbon::parse($chat['created_at'])->format('d M Y H:i') }}</div>
                </div>
            @empty
                <div class="item">
                    <strong>Belum ada percakapan</strong>
                    <div class="meta">Ketik pertanyaan tentang materi/quiz agar chatbot memberi saran belajar yang personal.</div>
                </div>
            @endforelse
        </div>

        <form method="POST" action="{{ route('siswa.chatbot') }}" style="display:grid;gap:8px;">
            @csrf
            <textarea name="message" rows="3" placeholder="Contoh: Saya masih bingung soal form validation, materi mana yang harus saya ulangi?" required style="width:100%;padding:10px;border:1px solid #c4dccf;border-radius:10px;font:inherit;">{{ old('message') }}</textarea>
            <div>
                <button type="submit" style="background:#0e9384;color:#fff;border:0;border-radius:10px;padding:10px 12px;font-weight:700;cursor:pointer;">Kirim ke Chatbot</button>
            </div>
        </form>
    </section>
</main>
</body>
</html>
