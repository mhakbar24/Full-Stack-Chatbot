<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Histori AI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body{margin:0;background:#f5f8ff;color:#163150;font-family:'Space Grotesk',sans-serif;}
        .wrap{max-width:980px;margin:0 auto;padding:22px;}
        .card{background:#fff;border:1px solid #dbe7f7;border-radius:14px;padding:18px;margin-top:14px;}
        .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;}
        .muted{color:#5d7393;font-size:.92rem;}
        .actions{display:flex;gap:8px;flex-wrap:wrap;}
        .btn{display:inline-block;text-decoration:none;padding:10px 12px;border-radius:10px;background:#2d4f78;color:#fff;font-weight:700;}
        .badge{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;font-weight:700;font-size:.85rem;border:1px solid transparent;}
        pre{white-space:pre-wrap;line-height:1.55;font-family:inherit;background:#f8fbff;padding:14px;border-radius:10px;border:1px solid #e3edf9;}
        @media (max-width:700px){.grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<div class="wrap">
    <h1 style="margin:0;">Detail Histori Analisis AI</h1>
    <div class="muted" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <span>Record #{{ $analysis->id }} • {{ $analysis->created_at->format('d M Y H:i') }}</span>
        <span class="badge" style="color:{{ $qualityBadge['color'] }};background:{{ $qualityBadge['bg_color'] }};border-color:{{ $qualityBadge['color'] }}33;">
            Kualitas: {{ $qualityBadge['label'] }}
        </span>
    </div>

    <div class="actions" style="margin-top:12px;">
        <a class="btn" href="{{ route('guru.history') }}">Kembali ke Histori</a>
        <a class="btn" href="{{ route('guru.dashboard') }}" style="background:#ff6b35;">Dashboard Guru</a>
    </div>

    <div class="card">
        <div class="grid">
            <div><strong>Siswa:</strong> {{ $analysis->student->name ?? '-' }}</div>
            <div><strong>Email:</strong> {{ $analysis->student->email ?? '-' }}</div>
            <div><strong>Total Quiz:</strong> {{ $analysis->total_quiz }}</div>
            <div><strong>Rata-rata:</strong> {{ number_format($analysis->average_score, 2) }}</div>
            <div><strong>Skor Terbaik:</strong> {{ $analysis->best_score }}</div>
            <div><strong>Skor Terbaru:</strong> {{ $analysis->latest_score }}</div>
        </div>
        <div style="margin-top:10px;"><strong>Konteks Guru:</strong> {{ $analysis->teacher_context ?: '-' }}</div>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Ringkasan</h3>
        <pre>{{ $analysisSections['ringkasan'] ?: '-' }}</pre>

        <h3>Kekuatan</h3>
        <pre>{{ $analysisSections['kekuatan'] ?: '-' }}</pre>

        <h3>Perlu Ditingkatkan</h3>
        <pre>{{ $analysisSections['perlu_ditingkatkan'] ?: '-' }}</pre>

        <h3>Tindak Lanjut</h3>
        <pre>{{ $analysisSections['tindak_lanjut'] ?: '-' }}</pre>
    </div>
</div>
</body>
</html>
