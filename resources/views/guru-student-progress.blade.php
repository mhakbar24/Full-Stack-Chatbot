<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progres Siswa - Guru</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body{margin:0;font-family:'Space Grotesk',sans-serif;background:#f7fbff;color:#163150;}
        .wrap{max-width:1080px;margin:0 auto;padding:20px;}
        .head{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;}
        .btn{text-decoration:none;border:0;border-radius:10px;padding:9px 12px;display:inline-block;font-weight:700;}
        .btn-main{background:#ff6b35;color:#fff;} .btn-alt{background:#e5eef9;color:#26496f;}
        .card{background:#fff;border:1px solid #d9e7f4;border-radius:14px;padding:16px;margin-top:14px;}
        .grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;}
        .stat{padding:12px;border:1px solid #e8eff8;border-radius:11px;background:#fbfdff;}
        .label{font-size:.82rem;color:#5b7593;}.value{font-size:1.25rem;font-weight:700;}
        .filters{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:8px;}
        select{border:1px solid #c7d9ee;border-radius:10px;padding:8px 10px;font-family:inherit;}
        table{width:100%;border-collapse:collapse;} th,td{padding:10px 8px;border-bottom:1px solid #edf2f8;text-align:left;}
        .bar-wrap{height:10px;border-radius:999px;background:#edf3fb;overflow:hidden;min-width:130px;}
        .bar{height:100%;border-radius:999px;} .bar-quiz{background:linear-gradient(90deg,#36a2eb,#2b7ebd);} .bar-materi{background:linear-gradient(90deg,#12b886,#0f8f6a);}
        textarea,input,select{border:1px solid #c7d9ee;border-radius:10px;padding:8px 10px;font-family:inherit;}
        .status-chip{display:inline-block;padding:3px 8px;border-radius:999px;font-size:.74rem;font-weight:700;}
        .s-kuat{background:#dcfce7;color:#166534;} .s-cukup{background:#fef3c7;color:#92400e;} .s-perlu{background:#fee2e2;color:#991b1b;}
        .risk{font-size:.74rem;font-weight:700;border-radius:999px;padding:3px 8px;display:inline-block;}
        .risk-high{background:#fee2e2;color:#991b1b;} .risk-mid{background:#fef3c7;color:#92400e;} .risk-low{background:#dcfce7;color:#166534;}
        @media (max-width:900px){.grid{grid-template-columns:1fr 1fr;}}
        @media (max-width:700px){.wrap{padding:12px;}table{min-width:650px;}.table-wrap{overflow-x:auto;}}
    </style>
</head>
<body>
<main class="wrap">
    <div class="head">
        <div>
            <h1 style="margin:0;">Detail Progres Siswa</h1>
            <div style="color:#5a7593;">{{ $student->name }} ({{ $student->email }})</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('guru.dashboard') }}" class="btn btn-alt">Kembali Dashboard</a>
            <a href="{{ route('guru.history') }}" class="btn btn-main">Laporan AI</a>
            <a href="{{ route('guru.students.progress.pdf', ['id' => $student->id, 'weeks' => $weeks]) }}" class="btn btn-alt">Export PDF</a>
        </div>
    </div>

    <section class="card">
        <div class="grid">
            <div class="stat"><div class="label">Total Attempt Quiz</div><div class="value">{{ $summary['total_attempts'] }}</div></div>
            <div class="stat"><div class="label">Rata-rata Skor</div><div class="value">{{ number_format($summary['average_score'], 2) }}</div></div>
            <div class="stat"><div class="label">Skor Terbaru</div><div class="value">{{ $summary['latest_score'] }}</div></div>
            <div class="stat"><div class="label">Cakupan Materi Guru</div><div class="value">{{ number_format($summary['materi_coverage'], 2) }}%</div></div>
        </div>
    </section>

    <section class="card">
        <form method="GET" class="filters">
            <label for="weeks"><strong>Rentang Minggu:</strong></label>
            <select id="weeks" name="weeks">
                <option value="4" {{ (int)$weeks === 4 ? 'selected' : '' }}>4 minggu</option>
                <option value="8" {{ (int)$weeks === 8 ? 'selected' : '' }}>8 minggu</option>
                <option value="12" {{ (int)$weeks === 12 ? 'selected' : '' }}>12 minggu</option>
                <option value="16" {{ (int)$weeks === 16 ? 'selected' : '' }}>16 minggu</option>
            </select>
            <button class="btn btn-main" type="submit" style="cursor:pointer;">Terapkan</button>
        </form>
    </section>

    <section class="card table-wrap">
        <h2 style="margin:0 0 10px;">Tren Mingguan (Quiz + Materi)</h2>
        <table>
            <thead>
                <tr>
                    <th>Minggu</th>
                    <th>Quiz</th>
                    <th>Bar Quiz</th>
                    <th>Akses Materi</th>
                    <th>Bar Materi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($weeklyProgress as $row)
                    <tr>
                        <td>{{ $row['week_label'] }}</td>
                        <td>{{ number_format($row['quiz_avg'], 2) }} ({{ $row['quiz_attempts'] }} attempt)</td>
                        <td><div class="bar-wrap"><div class="bar bar-quiz" style="width: {{ ($row['quiz_avg'] / $maxQuizAvg) * 100 }}%;"></div></div></td>
                        <td>{{ $row['materi_accesses'] }}</td>
                        <td><div class="bar-wrap"><div class="bar bar-materi" style="width: {{ ($row['materi_accesses'] / $maxMateriAccess) * 100 }}%;"></div></div></td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada data progres mingguan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="card table-wrap">
        <h2 style="margin:0 0 10px;">Aktivitas Materi per Kategori</h2>
        <table>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th>Total Akses</th>
                    <th>Materi Unik Diakses</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categorySummary as $item)
                    <tr>
                        <td>{{ $item['category'] }}</td>
                        <td>{{ $item['access_count'] }}</td>
                        <td>{{ $item['unique_materi'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Belum ada aktivitas materi per kategori.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="card table-wrap">
        <h2 style="margin:0 0 10px;">Analitik Kompetensi CP/ATP</h2>
        <table>
            <thead>
                <tr>
                    <th>Kompetensi</th>
                    <th>Attempt Quiz</th>
                    <th>Rata-rata Quiz</th>
                    <th>Akses Materi</th>
                    <th>Skor Mastery</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($competencySummary as $item)
                    @php
                        $statusClass = $item['status'] === 'kuat' ? 's-kuat' : ($item['status'] === 'cukup' ? 's-cukup' : 's-perlu');
                    @endphp
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td>{{ $item['quiz_attempts'] }}</td>
                        <td>{{ number_format($item['quiz_avg_score'], 2) }}</td>
                        <td>{{ $item['materi_accesses'] }}</td>
                        <td>{{ $item['mastery_score'] }}</td>
                        <td><span class="status-chip {{ $statusClass }}">{{ strtoupper(str_replace('_', ' ', $item['status'])) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada data kompetensi untuk rentang minggu ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="card">
        <h2 style="margin:0 0 10px;">Workflow Tindak Lanjut Guru</h2>
        <form method="POST" action="{{ route('guru.students.interventions.store', $student->id) }}" style="display:grid;gap:10px;">
            @csrf
            <div class="grid" style="grid-template-columns:2fr 1fr 1fr 1fr;">
                <input type="text" name="title" placeholder="Judul rencana (contoh: Remedial HTML Form)" required>
                <select name="risk_level" required>
                    <option value="tinggi">Risiko Tinggi</option>
                    <option value="sedang" selected>Risiko Sedang</option>
                    <option value="rendah">Risiko Rendah</option>
                </select>
                <input type="number" name="target_score" min="0" max="100" placeholder="Target skor">
                <input type="date" name="due_date">
            </div>
            <textarea name="action_items" rows="3" placeholder="Rincian tindakan: latihan, materi wajib, jadwal monitoring."></textarea>
            <textarea name="notes" rows="2" placeholder="Catatan guru (opsional)"></textarea>
            <div><button class="btn btn-main" type="submit" style="cursor:pointer;">Tambah Rencana</button></div>
        </form>

        <div class="table-wrap" style="margin-top:14px;">
            <table>
                <thead>
                    <tr>
                        <th>Rencana</th>
                        <th>Status</th>
                        <th>Target</th>
                        <th>Jatuh Tempo</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($interventions as $plan)
                        <tr>
                            <td>
                                <strong>{{ $plan->title }}</strong><br>
                                <span class="meta">{{ $plan->action_items ?: '-' }}</span><br>
                                <span class="risk {{ $plan->risk_level === 'tinggi' ? 'risk-high' : ($plan->risk_level === 'sedang' ? 'risk-mid' : 'risk-low') }}">Risiko {{ ucfirst($plan->risk_level) }}</span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('guru.interventions.update', $plan->id) }}" style="display:grid;gap:8px;">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="title" value="{{ $plan->title }}">
                                    <input type="hidden" name="risk_level" value="{{ $plan->risk_level }}">
                                    <input type="hidden" name="action_items" value="{{ $plan->action_items }}">
                                    <input type="hidden" name="target_score" value="{{ $plan->target_score }}">
                                    <input type="hidden" name="due_date" value="{{ optional($plan->due_date)->format('Y-m-d') }}">
                                    <input type="hidden" name="notes" value="{{ $plan->notes }}">
                                    <select name="status" onchange="this.form.submit()">
                                        <option value="belum_mulai" @selected($plan->status === 'belum_mulai')>Belum Mulai</option>
                                        <option value="berjalan" @selected($plan->status === 'berjalan')>Berjalan</option>
                                        <option value="selesai" @selected($plan->status === 'selesai')>Selesai</option>
                                    </select>
                                </form>
                            </td>
                            <td>{{ $plan->target_score !== null ? $plan->target_score : '-' }}</td>
                            <td>{{ $plan->due_date ? $plan->due_date->format('d M Y') : '-' }}</td>
                            <td>
                                <form method="POST" action="{{ route('guru.interventions.destroy', $plan->id) }}" onsubmit="return confirm('Hapus rencana ini?');">
                                    @csrf @method('DELETE')
                                    <button class="btn" style="background:#b0233f;color:#fff;cursor:pointer;" type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Belum ada rencana tindak lanjut untuk siswa ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
