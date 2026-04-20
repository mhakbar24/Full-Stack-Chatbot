<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Admin | Quiz AI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;800&family=Bricolage+Grotesque:opsz,wght@10..48,600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg0: #0f172a;
            --bg1: #1e293b;
            --bg2: #243b53;
            --mint: #7ee4c5;
            --ice: #dff8ff;
            --warn: #ffb703;
            --danger: #ff5d73;
            --card: rgba(255, 255, 255, 0.9);
            --ink: #10243a;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Manrope', sans-serif;
            color: var(--ink);
            min-height: 100vh;
            background:
                linear-gradient(140deg, var(--bg0), var(--bg1) 48%, var(--bg2)),
                radial-gradient(circle at 80% 15%, rgba(126, 228, 197, 0.35), transparent 40%);
        }

        .layout {
            max-width: 1150px;
            margin: 0 auto;
            padding: 22px;
            display: grid;
            gap: 16px;
            animation: intro .65s ease-out;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            color: #dff3ff;
            font-size: .9rem;
        }

        .topbar form { margin: 0; }

        .logout-btn {
            border: 0;
            border-radius: 10px;
            padding: 8px 12px;
            background: #7ee4c5;
            color: #10334f;
            font-weight: 800;
            cursor: pointer;
        }

        .hero {
            border-radius: 18px;
            padding: 24px;
            color: #fff;
            background: linear-gradient(130deg, rgba(255, 255, 255, 0.1), rgba(126, 228, 197, 0.14));
            border: 1px solid rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(6px);
        }

        .hero h1 {
            margin: 0;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: clamp(1.7rem, 3.8vw, 2.3rem);
            letter-spacing: .2px;
        }

        .hero p { margin: 10px 0 0; color: #d7f2ef; max-width: 700px; }

        .metrics {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .metric {
            background: var(--card);
            border-radius: 14px;
            padding: 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .metric .label { font-size: .8rem; color: #4f6885; }
        .metric .value { margin-top: 7px; font-size: 1.45rem; font-weight: 800; }

        .main {
            display: grid;
            gap: 14px;
            grid-template-columns: 1.2fr .8fr;
        }

        .panel {
            background: var(--card);
            border-radius: 14px;
            padding: 16px;
        }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 8px; text-align: left; font-size: .92rem; }
        thead th { color: #476182; border-bottom: 1px solid #d8e1eb; }
        tbody td { border-bottom: 1px solid #edf2f7; }

        .pill {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: .74rem;
            font-weight: 700;
        }

        .ok { background: #d6f7ed; color: #1c7a60; }
        .warn { background: #fff0cc; color: #9a6b00; }
        .danger { background: #ffdce2; color: #9b233a; }

        .actions { display: grid; gap: 10px; }
        .action {
            border-radius: 12px;
            padding: 12px;
            background: #f6f9ff;
            border: 1px solid #dfe8f3;
        }

        .action strong { display: block; margin-bottom: 4px; }

        .kpi {
            margin-top: 8px;
            height: 10px;
            border-radius: 10px;
            background: #e5ebf3;
            overflow: hidden;
        }

        .kpi > span {
            display: block;
            height: 100%;
            background: linear-gradient(90deg, #7ee4c5, #44b7ff);
        }

        @keyframes intro {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 920px) {
            .metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .main { grid-template-columns: 1fr; }
        }

        @media (max-width: 560px) {
            .layout { padding: 12px; }
            .metrics { grid-template-columns: 1fr; }
            th, td { font-size: .84rem; }
        }
    </style>
</head>
<body>
    <main class="layout">
        <div class="topbar">
            <div>Masuk sebagai: <strong>{{ $user->name }}</strong> ({{ strtoupper($user->role) }})</div>
            <form action="{{ route('web.logout') }}" method="POST">
                @csrf
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>

        <section class="hero">
            <h1>Admin Control Room: Quiz, Pengguna, dan Monitoring AI</h1>
            <p>Kelola guru, pantau performa sistem, dan awasi kualitas analisis AI agar proses belajar tetap stabil, aman, dan terukur.</p>
        </section>

        <section class="metrics">
            <article class="metric">
                <div class="label">Total Guru</div>
                <div class="value">{{ number_format($stats['total_teachers']) }}</div>
            </article>
            <article class="metric">
                <div class="label">Total Siswa</div>
                <div class="value">{{ number_format($stats['total_students']) }}</div>
            </article>
            <article class="metric">
                <div class="label">Request AI Hari Ini</div>
                <div class="value">{{ number_format($stats['ai_requests_today']) }}</div>
            </article>
            <article class="metric">
                <div class="label">Error Rate API</div>
                <div class="value">{{ number_format($stats['api_error_rate'], 1) }}%</div>
            </article>
        </section>

        <section class="main">
            <article class="panel">
                <h2>Ringkasan Aktivitas Guru</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Guru</th>
                            <th>Quiz Aktif</th>
                            <th>Analisis AI</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($teacherRows as $row)
                            <tr>
                                <td>{{ $row->name }}</td>
                                <td>{{ $row->quiz_count }}</td>
                                <td>{{ $row->analysis_count }}</td>
                                <td>
                                    @if ($row->analysis_count >= 10)
                                        <span class="pill ok">Sehat</span>
                                    @elseif ($row->analysis_count > 0)
                                        <span class="pill warn">Perlu Tinjau</span>
                                    @else
                                        <span class="pill danger">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">Belum ada data guru untuk ditampilkan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </article>

            <article class="panel">
                <h2>Quick Admin Actions</h2>
                <div class="actions">
                    <a class="action" href="{{ route('admin.users') }}" style="text-decoration:none; color:inherit;">
                        <strong>Manajemen Akun Guru</strong>
                        <span>Aktif/nonaktifkan akun dan reset akses.</span>
                    </a>
                    <a class="action" href="{{ route('admin.guru-siswa') }}" style="text-decoration:none; color:inherit;">
                        <strong>Daftar Guru dan Siswa</strong>
                        <span>Lihat ringkasan data guru dan siswa pada satu halaman.</span>
                    </a>
                    <div class="action">
                        <strong>Audit Analisis AI</strong>
                        <span>Review histori analisis untuk kualitas output.</span>
                    </div>
                    <div class="action">
                        <strong>Monitoring API Usage</strong>
                        <span>Pastikan kuota Gemini dan performa endpoint stabil.</span>
                    </div>
                </div>
                <h3 style="margin: 14px 0 6px;">Ketersediaan Sistem</h3>
                <div class="kpi"><span style="width:{{ $uptime }}%"></span></div>
                <small>Uptime {{ $uptime }}% dalam 30 hari terakhir</small>
            </article>
        </section>
    </main>
</body>
</html>
