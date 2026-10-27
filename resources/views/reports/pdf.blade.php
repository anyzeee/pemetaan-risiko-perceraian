<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Analisis Risiko Perceraian</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1e293b;
        }
        h1, h2 { text-align: center; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin-top: 24px; }
        p { line-height: 1.5; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px;
            text-align: left;
        }
        th { background: #e2e8f0; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <h1>Sistem Pengambilan Keputusan</h1>
    <p class="center">DP3AKB Kalimantan Selatan</p>
    <p class="center">Laporan Analisis Risiko Perceraian</p>
    <p class="center">{{ $reportDate }}</p>

    <hr>

    <h2>Ringkasan Eksekutif</h2>
    <p>Total Kasus Perceraian:
        <strong>{{ number_format($stats['totalCases'], 0, ',', '.') }}</strong>
    </p>
    <p>Kabupaten/Kota Berisiko Tinggi:
        <strong>{{ $stats['highRiskCount'] }}</strong>
    </p>
    <p>Periode:
        {{ $startYear }}{{ $endYear !== $startYear ? '–' . $endYear : '' }}
    </p>

    <h2>Distribusi Risiko</h2>
    <table>
        <thead>
            <tr>
                <th>Tingkat Risiko</th>
                <th>Jumlah Kabupaten/Kota</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($riskDistribution as $rd)
                <tr>
                    <td>{{ $rd['label'] }}</td>
                    <td>{{ $rd['count'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($reportType === 'detailed')
        <h2>Data Kabupaten/Kota</h2>
        <table>
            <thead>
                <tr>
                    <th>Kabupaten/Kota</th>
                    <th>Risiko</th>
                    <th>Skor</th>
                    <th>Kasus</th>
                    <th>Populasi</th>
                    <th>Tingkat Perceraian</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($filteredDistricts as $d)
                    <tr>
                        <td>{{ $d['name'] }}</td>
                        <td>{{ riskLabel($d['riskLevel']) }}</td>
                        <td>{{ $d['riskScore'] }}</td>
                        <td>{{ number_format($d['divorceCount'], 0, ',', '.') }}</td>
                        <td>{{ number_format($d['populationCount'], 0, ',', '.') }}</td>
                        <td>{{ $d['divorceRate'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Strategi yang Direkomendasikan</h2>
    <table>
        <thead>
            <tr>
                <th>Program</th>
                <th>Deskripsi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($recommendations as $rec)
                <tr>
                    <td>{{ $rec['title'] }}</td>
                    <td>{{ $rec['description'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 24px; text-align: center;">
        Laporan ini diproduksi secara otomatis untuk penggunaan internal.
    </p>
</body>
</html>