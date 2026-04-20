<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Feedback AI Siswa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body{margin:0;background:#f6fbff;color:#17314b;font-family:'Space Grotesk',sans-serif;}
        .wrap{max-width:980px;margin:0 auto;padding:20px;}
        .card{background:#fff;border:1px solid #d9e7f4;border-radius:14px;padding:16px;margin-top:14px;}
        .btn{text-decoration:none;border:0;border-radius:10px;padding:9px 12px;display:inline-block;font-weight:700;}
        .btn-main{background:#ff6b35;color:#fff;} .btn-alt{background:#e5eef9;color:#26496f;}
        .pill{display:inline-block;padding:4px 8px;border-radius:999px;font-size:.75rem;font-weight:700;}
        pre{white-space:pre-wrap;line-height:1.55;font-family:inherit;background:#f8fbff;padding:12px;border-radius:10px;border:1px solid #e8eff8;}
    </style>
</head>
<body>
<main class="wrap">
    <h1 style="margin:0;">Detail Feedback AI</h1>
    <div style="color:#5b7593;">{{ $student->name }} • {{ $analysis->created_at->format('d M Y H:i') }}</div>

    <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('siswa.history.ai') }}" class="btn btn-alt">Kembali Riwayat</a>
        <a href="{{ route('siswa.progress.overview') }}" class="btn btn-main">Ringkasan Progres</a>
    </div>

    <section class="card">
        <span class="pill" style="background:{{ $qualityBadge['bg_color'] }};color:{{ $qualityBadge['color'] }};">Kualitas: {{ $qualityBadge['label'] }}</span>
        <div style="margin-top:10px;">Total quiz: {{ $analysis->total_quiz }} • Rata-rata: {{ number_format($analysis->average_score, 2) }} • Best/Latest: {{ $analysis->best_score }} / {{ $analysis->latest_score }}</div>
        <div style="margin-top:6px;"><strong>Konteks:</strong> {{ $analysis->teacher_context ?: '-' }}</div>
    </section>

    <section class="card">
        <h3>Ringkasan</h3>
        <pre>{{ $analysisSections['ringkasan'] ?: '-' }}</pre>

        <h3>Kekuatan</h3>
        <pre>{{ $analysisSections['kekuatan'] ?: '-' }}</pre>

        <h3>Perlu Ditingkatkan</h3>
        <pre>{{ $analysisSections['perlu_ditingkatkan'] ?: '-' }}</pre>

        <h3>Tindak Lanjut</h3>
        <pre>{{ $analysisSections['tindak_lanjut'] ?: '-' }}</pre>
    </section>
</main>
</body>
</html>
