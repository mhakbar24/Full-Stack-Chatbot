<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Guru | Quiz AI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Fraunces:opsz,wght@9..144,600&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #132238;
            --paper: #fdf8ef;
            --accent: #ff6b35;
            --accent-soft: #ffd8c8;
            --teal: #2a9d8f;
            --card: #ffffff;
            --line: #e8dfd5;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Space Grotesk', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 90% 10%, #ffe2a8 0, transparent 30%),
                radial-gradient(circle at 15% 85%, #bde5ff 0, transparent 35%),
                linear-gradient(125deg, #fff8ec 0%, #f5f4ff 45%, #ebfdf8 100%);
            min-height: 100vh;
        }

        .wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px;
            animation: fadeIn .7s ease-out;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            color: #27496e;
            font-size: .9rem;
        }

        .topbar form { margin: 0; }

        .logout-btn {
            border: 0;
            border-radius: 10px;
            padding: 8px 12px;
            background: #1f3a5c;
            color: #fff;
            cursor: pointer;
            font-weight: 700;
        }

        .hero {
            background: linear-gradient(135deg, #132238 0%, #1f3a5c 65%, #265f78 100%);
            color: #fff;
            border-radius: 20px;
            padding: 28px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 45px rgba(10, 27, 44, 0.25);
        }

        .hero::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            top: -90px;
            right: -70px;
        }

        .hero h1 {
            margin: 0;
            font-family: 'Fraunces', serif;
            font-size: clamp(1.7rem, 4vw, 2.4rem);
            line-height: 1.15;
            max-width: 550px;
        }

        .hero p {
            margin: 14px 0 0;
            max-width: 620px;
            color: #e3ebf8;
        }

        .grid {
            display: grid;
            gap: 16px;
            margin-top: 18px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .card {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid var(--line);
            padding: 18px;
            box-shadow: 0 8px 20px rgba(13, 28, 42, 0.08);
            animation: rise .5s ease-out both;
        }

        .card:nth-child(2) { animation-delay: .08s; }
        .card:nth-child(3) { animation-delay: .16s; }
        .card:nth-child(4) { animation-delay: .24s; }

        .stat-label { font-size: .85rem; color: #5b6e82; }
        .stat-value { margin-top: 6px; font-size: 1.5rem; font-weight: 700; }

        .section {
            margin-top: 20px;
            display: grid;
            gap: 16px;
            grid-template-columns: 1.3fr 1fr;
        }

        .panel {
            background: #fff;
            border-radius: 16px;
            border: 1px solid var(--line);
            padding: 20px;
        }

        .panel h2 {
            margin: 0 0 12px;
            font-size: 1.05rem;
        }

        .task {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-radius: 12px;
            background: #faf8f5;
            margin-bottom: 10px;
        }

        .task small { color: #607084; }

        .badge {
            font-size: .75rem;
            border-radius: 999px;
            padding: 4px 10px;
            font-weight: 700;
        }

        .b-orange { background: var(--accent-soft); color: #a83d1e; }
        .b-teal { background: #d3f3ef; color: #196c63; }

        .button-row {
            display: flex;
            gap: 10px;
            margin-top: 14px;
            flex-wrap: wrap;
        }

        .btn {
            border: 0;
            border-radius: 12px;
            padding: 10px 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-main { background: var(--accent); color: #fff; }
        .btn-alt { background: #eaf2ff; color: #204473; }

        .timeline-item {
            border-left: 3px solid #dfeaf6;
            padding-left: 12px;
            margin-bottom: 12px;
        }

        .timeline-item strong { display: block; margin-bottom: 4px; }

        .content-group {
            margin-top: 12px;
        }

        .content-group h3 {
            margin: 0 0 8px;
            font-size: .95rem;
            color: #24486f;
        }

        .activity-tag {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: .73rem;
            font-weight: 700;
            margin-right: 6px;
            background: #eaf2ff;
            color: #21466f;
        }

        .activity-tag.materi {
            background: #e2f9f4;
            color: #176b5e;
        }

        .score-chip {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: .73rem;
            font-weight: 700;
            background: #fef3c7;
            color: #92400e;
        }

        .progress-table-wrap {
            overflow-x: auto;
            border: 1px solid #e4ded6;
            border-radius: 12px;
            background: #fff;
        }

        .progress-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
        }

        .progress-table th,
        .progress-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee7dd;
            text-align: left;
            font-size: .92rem;
        }

        .progress-table th {
            background: #fff6ea;
            color: #334e70;
            font-weight: 700;
        }

        .progress-table tr:last-child td { border-bottom: 0; }

        .trend {
            display: inline-block;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: .76rem;
            font-weight: 700;
        }

        .trend-up { background: #dcfce7; color: #166534; }
        .trend-flat { background: #fef3c7; color: #92400e; }
        .trend-down { background: #fee2e2; color: #991b1b; }

        .subtle { color: #5c7086; font-size: .85rem; }

        .filters {
            display: grid;
            grid-template-columns: 1.3fr repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-top: 12px;
        }

        .filters input,
        .filters select {
            width: 100%;
            border: 1px solid #d9e3f1;
            border-radius: 10px;
            padding: 9px 10px;
            font-family: inherit;
            font-size: .9rem;
            background: #fff;
            color: #163150;
        }

        .filters .actions {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .link-reset {
            display: inline-block;
            text-decoration: none;
            padding: 9px 11px;
            border-radius: 10px;
            background: #eef3fb;
            color: #284b73;
            font-weight: 700;
            font-size: .86rem;
        }

        .pager {
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .pager .meta {
            color: #5c7086;
            font-size: .86rem;
        }

        .pager .controls {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .pager-link {
            text-decoration: none;
            padding: 8px 11px;
            border-radius: 9px;
            background: #e9f0fb;
            color: #21466f;
            font-weight: 700;
            font-size: .84rem;
        }

        .pager-link.disabled {
            pointer-events: none;
            opacity: .45;
        }

        .ai-form {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 6px;
            align-items: center;
        }

        .ai-context {
            border: 1px solid #c7d8ee;
            border-radius: 8px;
            padding: 6px 8px;
            min-width: 180px;
            font-size: .82rem;
        }

        .btn-ai {
            background: #0f766e;
            color: #fff;
            border: 0;
            border-radius: 9px;
            padding: 6px 10px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-ai[disabled] {
            opacity: .6;
            cursor: not-allowed;
        }

        .ai-feedback {
            margin-top: 6px;
            padding: 6px 8px;
            border-radius: 8px;
            font-size: .8rem;
            line-height: 1.3;
        }

        .ai-feedback.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .ai-feedback.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .ai-feedback.fade-out {
            opacity: 0;
            transform: translateY(-4px);
            transition: opacity .35s ease, transform .35s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes rise {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 920px) {
            .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .section { grid-template-columns: 1fr; }
            .filters { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 560px) {
            .wrap { padding: 14px; }
            .hero { padding: 18px; }
            .grid { grid-template-columns: 1fr; }
            .filters { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <main class="wrap">
        <div class="topbar">
            <div>Masuk sebagai: <strong>{{ $user->name }}</strong> ({{ strtoupper($user->role) }})</div>
            <form action="{{ route('web.logout') }}" method="POST">
                @csrf
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>

        <section class="hero">
            <h1>Dashboard Guru: Pantau Kemajuan Siswa Berbasis AI</h1>
            <p>Gunakan quiz dan analisis AI untuk melihat tren belajar, memberi umpan balik terarah, dan menyusun tindak lanjut pembelajaran per siswa.</p>
        </section>

        @if (session('status'))
            <section class="panel" style="margin-top:12px; border-color:#cfe8d8; background:#f5fff8;">
                <strong style="color:#17653e;">{{ session('status') }}</strong>
            </section>
        @endif

        <section class="grid">
            <article class="card">
                <div class="stat-label">Total Siswa Aktif</div>
                <div class="stat-value">{{ number_format($stats['total_students']) }}</div>
            </article>
            <article class="card">
                <div class="stat-label">Quiz Minggu Ini</div>
                <div class="stat-value">{{ number_format($stats['quiz_this_week']) }}</div>
            </article>
            <article class="card">
                <div class="stat-label">Analisis AI Dibuat</div>
                <div class="stat-value">{{ number_format($stats['analysis_this_week']) }}</div>
            </article>
            <article class="card">
                <div class="stat-label">Rata-rata Skor</div>
                <div class="stat-value">{{ number_format($stats['average_score'], 2) }}</div>
            </article>
        </section>

        <section class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
            <article class="card">
                <div class="stat-label">Total Materi Guru</div>
                <div class="stat-value">{{ number_format($stats['total_materi_guru']) }}</div>
            </article>
            <article class="card">
                <div class="stat-label">Rata-rata Cakupan Materi Siswa</div>
                <div class="stat-value">{{ number_format($stats['overall_materi_coverage'], 2) }}%</div>
            </article>
        </section>

        <section class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
            <article class="card">
                <div class="stat-label">Early Warning Tinggi</div>
                <div class="stat-value">{{ number_format($stats['at_risk_students']) }}</div>
            </article>
            <article class="card">
                <div class="stat-label">Early Warning Sedang</div>
                <div class="stat-value">{{ number_format($stats['medium_risk_students']) }}</div>
            </article>
        </section>

        <section class="section">
            <article class="panel">
                <h2>Aksi Cepat Guru</h2>
                <div class="task">
                    <div>
                        <strong>Buat Quiz Baru</strong>
                        <small>Susun soal untuk evaluasi pekanan</small>
                    </div>
                    <span class="badge b-orange">Quiz</span>
                </div>
                <div class="task">
                    <div>
                        <strong>Generate Analisis AI</strong>
                        <small>Ringkasan perkembangan 5 hasil quiz terbaru</small>
                    </div>
                    <span class="badge b-teal">AI</span>
                </div>
                <div class="task">
                    <div>
                        <strong>Lihat Histori Siswa</strong>
                        <small>Filter by tanggal dan detail per analisis</small>
                    </div>
                    <span class="badge b-orange">Riwayat</span>
                </div>
                <div class="button-row">
                    @php
                        $pdfFilters = array_filter([
                            'q' => $filters['q'] ?? '',
                            'trend' => $filters['trend'] ?? '',
                            'risk' => $filters['risk'] ?? '',
                            'from' => $filters['from'] ?? '',
                            'to' => $filters['to'] ?? '',
                        ], function ($value) {
                            return $value !== null && $value !== '';
                        });
                        $weekStart = now()->startOfWeek()->toDateString();
                        $weekEnd = now()->endOfWeek()->toDateString();
                    @endphp
                    <a class="btn btn-main" href="{{ route('guru.quizzes') }}">Kelola Quiz</a>
                    <a class="btn btn-alt" href="{{ route('guru.history') }}">Buka Laporan AI</a>
                    <a class="btn btn-alt" href="{{ route('guru.materis') }}">Kelola Materi</a>
                    <a class="btn btn-alt" href="{{ route('guru.early-warning.pdf', $pdfFilters) }}">Download PDF Early Warning</a>
                </div>
                <div class="button-row" style="margin-top:8px;">
                    <a class="btn btn-alt" href="{{ route('guru.dashboard', ['risk' => 'tinggi', 'from' => $weekStart, 'to' => $weekEnd, 'sort' => 'avg_score', 'direction' => 'asc']) }}">Preset: Risiko Tinggi Minggu Ini</a>
                    <a class="btn btn-alt" href="{{ route('guru.dashboard', ['risk' => 'sedang', 'from' => $weekStart, 'to' => $weekEnd, 'sort' => 'avg_score', 'direction' => 'asc']) }}">Preset: Risiko Sedang Minggu Ini</a>
                    <a class="btn btn-alt" href="{{ route('guru.dashboard') }}">Preset: Reset Filter</a>
                </div>
            </article>

            <article class="panel">
                <h2>Aktivitas Terbaru</h2>
                @forelse ($recentActivities as $activity)
                    <div class="timeline-item">
                        <strong>Analisis: {{ $activity->student->name ?? 'Siswa tidak diketahui' }}</strong>
                        <small>{{ $activity->created_at->format('d M Y H:i') }} • {{ \Illuminate\Support\Str::limit($activity->analysis, 64) }}</small>
                    </div>
                @empty
                    <div class="timeline-item">
                        <strong>Belum ada aktivitas</strong>
                        <small>Analisis AI terbaru akan muncul di sini.</small>
                    </div>
                @endforelse
            </article>
        </section>

        <section class="section">
            <article class="panel">
                <h2>Konten Pembelajaran Terbaru</h2>

                <div class="content-group">
                    <h3>Quiz Terbaru</h3>
                    @forelse($latestTeacherQuizzes as $quiz)
                        <div class="item">
                            <strong>{{ $quiz->title }}</strong>
                            <div class="meta">{{ $quiz->questions_count }} soal • {{ $quiz->created_at->format('d M Y H:i') }}</div>
                        </div>
                    @empty
                        <div class="item">
                            <strong>Belum ada quiz</strong>
                            <div class="meta">Tambahkan quiz dari menu Kelola Quiz.</div>
                        </div>
                    @endforelse
                </div>

                <div class="content-group">
                    <h3>Materi Terbaru</h3>
                    @forelse($latestTeacherMateris as $materi)
                        <div class="item">
                            <strong>{{ $materi->title }}</strong>
                            <div class="meta">Kategori: {{ $materi->category ?: 'Umum' }} • {{ $materi->created_at->format('d M Y H:i') }}</div>
                        </div>
                    @empty
                        <div class="item">
                            <strong>Belum ada materi</strong>
                            <div class="meta">Tambahkan materi dari menu Kelola Materi.</div>
                        </div>
                    @endforelse
                </div>
            </article>

            <article class="panel">
                <h2>Aktivitas Siswa Terkini</h2>
                @forelse($recentStudentLearningActivities as $activity)
                    <div class="timeline-item">
                        <span class="activity-tag {{ $activity['type'] === 'materi' ? 'materi' : '' }}">{{ strtoupper($activity['type']) }}</span>
                        @if($activity['type'] === 'quiz')
                            <span class="score-chip">Skor: {{ $activity['score'] }}</span>
                        @endif
                        <strong style="margin-top:6px;">{{ $activity['student_name'] }} • {{ $activity['title'] }}</strong>
                        <small>{{ $activity['student_email'] }} • {{ $activity['activity_at']->format('d M Y H:i') }}</small>
                    </div>
                @empty
                    <div class="timeline-item">
                        <strong>Belum ada aktivitas siswa</strong>
                        <small>Aktivitas membaca materi dan mengerjakan quiz siswa akan muncul di sini.</small>
                    </div>
                @endforelse
            </article>
        </section>

        <section class="panel" style="margin-top:16px;">
            <h2>Perkembangan Siswa (Quiz + Materi)</h2>
            <p class="subtle">Data materi dihitung dari log akses saat siswa membuka detail materi atau endpoint track materi.</p>
            <div class="button-row" style="margin-top:10px;">
                <a class="link-reset" href="{{ route('guru.dashboard', ['risk' => 'tinggi', 'from' => now()->startOfWeek()->toDateString(), 'to' => now()->endOfWeek()->toDateString(), 'sort' => 'avg_score', 'direction' => 'asc']) }}">Risiko Tinggi Minggu Ini</a>
                <a class="link-reset" href="{{ route('guru.dashboard', ['risk' => 'sedang', 'from' => now()->startOfWeek()->toDateString(), 'to' => now()->endOfWeek()->toDateString(), 'sort' => 'avg_score', 'direction' => 'asc']) }}">Risiko Sedang Minggu Ini</a>
                <a class="link-reset" href="{{ route('guru.dashboard') }}">Reset</a>
            </div>

            <form method="GET" action="{{ route('guru.dashboard') }}" class="filters">
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama / email siswa">

                <select name="trend">
                    <option value="">Semua Tren</option>
                    <option value="meningkat" {{ ($filters['trend'] ?? '') === 'meningkat' ? 'selected' : '' }}>Meningkat</option>
                    <option value="stabil" {{ ($filters['trend'] ?? '') === 'stabil' ? 'selected' : '' }}>Stabil</option>
                    <option value="menurun" {{ ($filters['trend'] ?? '') === 'menurun' ? 'selected' : '' }}>Menurun</option>
                </select>

                <select name="risk">
                    <option value="">Semua Risiko</option>
                    <option value="tinggi" {{ ($filters['risk'] ?? '') === 'tinggi' ? 'selected' : '' }}>Risiko Tinggi</option>
                    <option value="sedang" {{ ($filters['risk'] ?? '') === 'sedang' ? 'selected' : '' }}>Risiko Sedang</option>
                    <option value="rendah" {{ ($filters['risk'] ?? '') === 'rendah' ? 'selected' : '' }}>Risiko Rendah</option>
                </select>

                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}">
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}">

                <select name="sort">
                    <option value="avg_score" {{ ($filters['sort'] ?? 'avg_score') === 'avg_score' ? 'selected' : '' }}>Urut: Skor Rata-rata</option>
                    <option value="latest_score" {{ ($filters['sort'] ?? '') === 'latest_score' ? 'selected' : '' }}>Urut: Skor Terbaru</option>
                    <option value="materi_coverage" {{ ($filters['sort'] ?? '') === 'materi_coverage' ? 'selected' : '' }}>Urut: Cakupan Materi</option>
                    <option value="materi_accesses" {{ ($filters['sort'] ?? '') === 'materi_accesses' ? 'selected' : '' }}>Urut: Akses Materi</option>
                    <option value="name" {{ ($filters['sort'] ?? '') === 'name' ? 'selected' : '' }}>Urut: Nama Siswa</option>
                </select>

                <div class="actions">
                    <select name="per_page">
                        <option value="10" {{ (int)($filters['per_page'] ?? 10) === 10 ? 'selected' : '' }}>10 / halaman</option>
                        <option value="20" {{ (int)($filters['per_page'] ?? 10) === 20 ? 'selected' : '' }}>20 / halaman</option>
                        <option value="30" {{ (int)($filters['per_page'] ?? 10) === 30 ? 'selected' : '' }}>30 / halaman</option>
                        <option value="50" {{ (int)($filters['per_page'] ?? 10) === 50 ? 'selected' : '' }}>50 / halaman</option>
                    </select>
                    <select name="direction">
                        <option value="desc" {{ ($filters['direction'] ?? 'desc') === 'desc' ? 'selected' : '' }}>Desc</option>
                        <option value="asc" {{ ($filters['direction'] ?? '') === 'asc' ? 'selected' : '' }}>Asc</option>
                    </select>
                    <button type="submit" class="btn btn-main">Terapkan</button>
                    <a href="{{ route('guru.dashboard') }}" class="link-reset">Reset</a>
                </div>
            </form>

            @if(($studentProgressRows ?? collect())->count() === 0)
                <div class="task" style="margin-top:12px;">
                    <div>
                        <strong>Belum ada data progres</strong>
                        <small>Pastikan siswa sudah mengerjakan quiz dan membuka materi.</small>
                    </div>
                </div>
            @else
                <div class="progress-table-wrap" style="margin-top:12px;">
                    <table class="progress-table">
                        <thead>
                            <tr>
                                <th>Siswa</th>
                                <th>Quiz</th>
                                <th>Skor Rata-rata</th>
                                <th>Skor Terbaru</th>
                                <th>Tren</th>
                                <th>Akses Materi</th>
                                <th>Cakupan Materi</th>
                                <th>Risiko</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($studentProgressRows as $row)
                                @php
                                    $trendClass = $row['quiz_trend'] === 'meningkat'
                                        ? 'trend-up'
                                        : ($row['quiz_trend'] === 'menurun' ? 'trend-down' : 'trend-flat');
                                    $aiFeedback = session('ai_feedback');
                                    $hasAiFeedback = is_array($aiFeedback)
                                        && ((int) ($aiFeedback['student_id'] ?? 0) === (int) $row['student_id']);
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $row['student_name'] }}</strong><br>
                                        <span class="subtle">{{ $row['student_email'] }}</span>
                                    </td>
                                    <td>{{ $row['quiz_total_attempts'] }}</td>
                                    <td>{{ number_format($row['quiz_average_score'], 2) }}</td>
                                    <td>{{ $row['quiz_latest_score'] }}</td>
                                    <td><span class="trend {{ $trendClass }}">{{ ucfirst($row['quiz_trend']) }}</span></td>
                                    <td>{{ $row['materi_total_access_logs'] }}</td>
                                    <td>{{ number_format($row['materi_coverage_percent'], 2) }}%</td>
                                    <td>
                                        @if(($row['risk_level'] ?? 'rendah') === 'tinggi')
                                            <span class="trend trend-down">Tinggi</span>
                                        @elseif(($row['risk_level'] ?? 'rendah') === 'sedang')
                                            <span class="trend trend-flat">Sedang</span>
                                        @else
                                            <span class="trend trend-up">Rendah</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                            <a href="{{ route('guru.students.progress', $row['student_id']) }}" class="btn" style="background:#1f3a5c;color:#fff;padding:6px 10px;border-radius:9px;text-decoration:none;">Detail</a>
                                            <form method="POST" action="{{ route('guru.students.destroy', $row['student_id']) }}" onsubmit="return confirm('Yakin hapus siswa ini? Semua progres terkait juga akan terhapus.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn" style="background:#b4233f;color:#fff;padding:6px 10px;border-radius:9px;">Hapus</button>
                                            </form>
                                        </div>
                                        <form method="POST" action="{{ route('guru.students.progress-ai', $row['student_id']) }}" class="ai-form js-ai-generate-form">
                                            @csrf
                                            <input type="text" name="context" class="ai-context" placeholder="Konteks opsional (mis. fokus HTML dasar)">
                                            <button type="submit" class="btn-ai js-ai-generate-btn">Generate AI</button>
                                        </form>
                                        @if($hasAiFeedback)
                                            <div class="ai-feedback {{ ($aiFeedback['status'] ?? '') === 'success' ? 'success' : 'error' }} js-ai-feedback">
                                                {{ $aiFeedback['message'] ?? '' }}
                                                @if(($aiFeedback['status'] ?? '') === 'success' && !empty($aiFeedback['history_id']))
                                                    <a href="{{ route('guru.history.detail', $aiFeedback['history_id']) }}" style="font-weight:700;color:inherit;">Lihat detail</a>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pager">
                    <div class="meta">
                        Menampilkan {{ $studentProgressRows->firstItem() ?? 0 }} - {{ $studentProgressRows->lastItem() ?? 0 }} dari {{ $studentProgressRows->total() }} siswa
                    </div>
                    <div class="controls">
                        <a href="{{ $studentProgressRows->previousPageUrl() ?: '#' }}" class="pager-link {{ $studentProgressRows->onFirstPage() ? 'disabled' : '' }}">Sebelumnya</a>
                        <span class="meta">Halaman {{ $studentProgressRows->currentPage() }} / {{ $studentProgressRows->lastPage() }}</span>
                        <a href="{{ $studentProgressRows->nextPageUrl() ?: '#' }}" class="pager-link {{ $studentProgressRows->hasMorePages() ? '' : 'disabled' }}">Berikutnya</a>
                    </div>
                </div>
            @endif
        </section>
    </main>
    <script>
        document.querySelectorAll('.js-ai-generate-form').forEach(function (form) {
            form.addEventListener('submit', function () {
                var button = form.querySelector('.js-ai-generate-btn');
                if (!button) {
                    return;
                }
                button.disabled = true;
                button.textContent = 'Menganalisis...';
            });
        });

        document.querySelectorAll('.js-ai-feedback').forEach(function (feedback) {
            setTimeout(function () {
                feedback.classList.add('fade-out');
                setTimeout(function () {
                    feedback.remove();
                }, 380);
            }, 5000);
        });
    </script>
</body>
</html>
