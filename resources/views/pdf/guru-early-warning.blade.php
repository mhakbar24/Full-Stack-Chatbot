<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Early Warning Guru</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; }
        h1 { margin: 0 0 6px; font-size: 18px; }
        .meta { margin-bottom: 12px; color: #4b5563; }
        .summary { margin: 10px 0 14px; }
        .badge { display: inline-block; margin-right: 8px; padding: 4px 8px; border-radius: 8px; background: #eef2ff; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 7px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .risk-tinggi { color: #991b1b; font-weight: 700; }
        .risk-sedang { color: #92400e; font-weight: 700; }
        .risk-rendah { color: #166534; font-weight: 700; }
    </style>
</head>
<body>
    <h1>Laporan Early Warning Siswa</h1>
    <div class="meta">
        Guru: {{ $teacher->name }} ({{ $teacher->email }})<br>
        Dibuat: {{ $generatedAt->format('d M Y H:i') }}
        @php
            $filterLabels = [];
            if (!empty($filters['q'])) {
                $filterLabels[] = 'Kata kunci: ' . $filters['q'];
            }
            if (!empty($filters['trend'])) {
                $filterLabels[] = 'Tren: ' . ucfirst($filters['trend']);
            }
            if (!empty($filters['risk'])) {
                $filterLabels[] = 'Risiko: ' . ucfirst($filters['risk']);
            }
            if (!empty($filters['from']) || !empty($filters['to'])) {
                $filterLabels[] = 'Periode: ' . ($filters['from'] ?: '-') . ' s/d ' . ($filters['to'] ?: '-');
            }
        @endphp
        @if(!empty($filterLabels))
            <br>
            Filter: {{ implode(' | ', $filterLabels) }}
        @endif
    </div>

    <div class="summary">
        <span class="badge">Total Siswa: <strong>{{ $summary['total_students'] }}</strong></span>
        <span class="badge">Risiko Tinggi: <strong>{{ $summary['risk_tinggi'] }}</strong></span>
        <span class="badge">Risiko Sedang: <strong>{{ $summary['risk_sedang'] }}</strong></span>
        <span class="badge">Risiko Rendah: <strong>{{ $summary['risk_rendah'] }}</strong></span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Siswa</th>
                <th>Quiz</th>
                <th>Rata-rata</th>
                <th>Terbaru</th>
                <th>Tren</th>
                <th>Akses Materi</th>
                <th>Cakupan</th>
                <th>Risiko</th>
                <th>Rekomendasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                @php
                    $riskClass = $row['risk_level'] === 'tinggi'
                        ? 'risk-tinggi'
                        : ($row['risk_level'] === 'sedang' ? 'risk-sedang' : 'risk-rendah');
                @endphp
                <tr>
                    <td>
                        <strong>{{ $row['student_name'] }}</strong><br>
                        {{ $row['student_email'] }}
                    </td>
                    <td>{{ $row['quiz_total_attempts'] }}</td>
                    <td>{{ number_format($row['quiz_average_score'], 2) }}</td>
                    <td>{{ $row['quiz_latest_score'] }}</td>
                    <td>{{ ucfirst($row['quiz_trend']) }}</td>
                    <td>{{ $row['materi_total_access_logs'] }}</td>
                    <td>{{ number_format($row['materi_coverage_percent'], 2) }}%</td>
                    <td class="{{ $riskClass }}">{{ strtoupper($row['risk_level']) }}</td>
                    <td>{{ $row['recommendation'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">Belum ada data siswa untuk dievaluasi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
