<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Materi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body{margin:0;font-family:'Space Grotesk',sans-serif;background:#f6fbff;color:#17314b;}
        .wrap{max-width:980px;margin:0 auto;padding:20px;}
        .btn{display:inline-block;text-decoration:none;padding:9px 12px;border-radius:10px;font-weight:700;background:#e7f0fb;color:#21476f;}
        .card{margin-top:14px;background:#fff;border:1px solid #dbe8f6;border-radius:14px;padding:16px;}
        .meta{color:#5f7693;font-size:.9rem;}
        .rel-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;}
        .rel-item{padding:10px;border:1px solid #e2ecf8;border-radius:10px;background:#fbfdff;}
        .chat-list{max-height:320px;overflow:auto;display:grid;gap:8px;margin:8px 0 10px;}
        .chat-bubble{border:1px solid #e2ecf8;border-radius:12px;padding:10px;background:#f8fbff;}
        .chat-bubble.bot{background:#eefcf8;border-color:#d2f0e7;}
        .chat-label{font-size:.76rem;font-weight:700;color:#4f6f8f;text-transform:uppercase;letter-spacing:.03em;margin-bottom:4px;}
        @media (max-width:720px){.rel-grid{grid-template-columns:1fr;}.wrap{padding:12px;}}
    </style>
</head>
<body>
<main class="wrap">
    <a href="{{ route('siswa.materis') }}" class="btn">Kembali ke Daftar Materi</a>

    <section class="card">
        <h1 style="margin:0;">{{ $materi->title }}</h1>
        <div class="meta" style="margin-top:6px;">Kategori: {{ $materi->category ?: 'Umum' }} • Guru: {{ $materi->teacher->name ?? '-' }}</div>

        @if($materi->image)
            <div style="margin-top:12px;">
                <img src="{{ asset('storage/' . $materi->image) }}" alt="{{ $materi->title }}" style="max-width:100%;border-radius:10px;border:1px solid #deebf9;">
            </div>
        @endif

        <div style="margin-top:12px;line-height:1.7;white-space:pre-wrap;">{{ $materi->description ?: 'Belum ada deskripsi materi.' }}</div>
    </section>

    <section class="card">
        <h3 style="margin-top:0;">Chatbot Pendamping Materi</h3>
        <div class="meta" style="margin-bottom:8px;">Tanya langsung tentang materi ini. Chatbot akan mengaitkan penjelasan dengan quiz dan materi yang sudah kamu pelajari.</div>

        @if (session('chatbot_success'))
            <div style="color:#166534;font-weight:700;margin-bottom:8px;">{{ session('chatbot_success') }}</div>
        @endif
        @if (session('chatbot_error'))
            <div style="color:#b42318;font-weight:700;margin-bottom:8px;">{{ session('chatbot_error') }}</div>
        @endif
        @if ($errors->has('message'))
            <div style="color:#b42318;font-weight:700;margin-bottom:8px;">{{ $errors->first('message') }}</div>
        @endif

        <div class="chat-list">
            @forelse(($materiChatbotHistory ?? []) as $chat)
                <div class="chat-bubble">
                    <div class="chat-label">Siswa</div>
                    <div>{{ $chat['question'] }}</div>
                </div>
                <div class="chat-bubble bot">
                    <div class="chat-label">Chatbot</div>
                    <div style="white-space:pre-line;">{{ $chat['answer'] }}</div>
                    @if(!empty($chat['citations']) && is_array($chat['citations']))
                        <div class="meta" style="margin-top:6px;">
                            <strong>Sumber:</strong>
                            @foreach($chat['citations'] as $ref)
                                <a href="{{ $ref['url'] ?? '#' }}" style="color:#1f4f79;font-weight:700;text-decoration:none;">{{ $ref['title'] ?? 'Materi' }}</a>@if(!$loop->last), @endif
                            @endforeach
                        </div>
                    @endif
                    <div class="meta" style="margin-top:4px;">{{ \Illuminate\Support\Carbon::parse($chat['created_at'])->format('d M Y H:i') }}</div>
                </div>
            @empty
                <div class="rel-item">Belum ada percakapan. Tanyakan konsep yang ingin kamu pahami dari materi ini.</div>
            @endforelse
        </div>

        <form method="POST" action="{{ route('siswa.materis.chatbot', $materi->id) }}" style="display:grid;gap:8px;">
            @csrf
            <textarea name="message" rows="3" placeholder="Contoh: Bagian mana dari materi ini yang paling penting untuk menjawab quiz HTML form?" required style="width:100%;padding:10px;border:1px solid #d7e6f5;border-radius:10px;font:inherit;">{{ old('message') }}</textarea>
            <div><button type="submit" class="btn" style="background:#0e9384;color:#fff;border:0;">Tanya Chatbot</button></div>
        </form>
    </section>

    <section class="card">
        <h3 style="margin-top:0;">Materi Terkait</h3>
        <div class="rel-grid">
            @forelse($relatedMateris as $item)
                <div class="rel-item">
                    <strong>{{ $item->title }}</strong>
                    <div class="meta">{{ $item->category ?: 'Umum' }} • {{ $item->created_at->format('d M Y') }}</div>
                    <a href="{{ route('siswa.materis.show', $item->id) }}" class="btn" style="margin-top:8px;">Buka</a>
                </div>
            @empty
                <div class="rel-item">Tidak ada materi terkait.</div>
            @endforelse
        </div>
    </section>
</main>
</body>
</html>
