<?php

namespace App\Http\Controllers;

use App\Models\District;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        $reportType = $request->query('report_type', 'summary');
$startYear = (int) $request->query('start_year', date('Y'));
$endYear = (int) $request->query('end_year', $startYear);
        $selectedDistricts= array_map('intval', $request->query('districts', []));
        $showPreview      = $request->has('show_preview')
            ? (bool) $request->query('show_preview') : true;

        $allDistricts      = $this->getAllDistrictsData($startYear, $endYear);
        $filteredDistricts = count($selectedDistricts) > 0
            ? array_values(array_filter($allDistricts, fn ($d) => in_array($d['id'], $selectedDistricts, true)))
            : $allDistricts;

        $stats = [
            'totalCases'    => array_sum(array_column($filteredDistricts, 'divorceCount')),
            'highRiskCount' => count(array_filter($filteredDistricts, fn ($d) => in_array($d['riskLevel'], ['critical', 'high'], true))),
        ];

        $riskDistribution = collect(['critical', 'high', 'moderate', 'low'])->map(function ($level) use ($filteredDistricts) {
            return [
                'label' => riskLabel($level),
                'count' => count(array_filter($filteredDistricts, fn ($d) => $d['riskLevel'] === $level)),
            ];
        })->all();

        $recommendations = $this->getRecommendations();

        \Carbon\Carbon::setLocale('id');

        return view('reports.index', [
            'reportType'        => $reportType,
            'startYear'         => $startYear,
            'endYear'           => $endYear,
            'allDistricts'      => $allDistricts,
            'selectedDistricts' => $selectedDistricts,
            'filteredDistricts' => $filteredDistricts,
            'showPreview'       => $showPreview,
            'stats'             => $stats,
            'riskDistribution'  => $riskDistribution,
            'recommendations'   => $recommendations,
            'reportDate'        => now()->translatedFormat('l, j F Y'),
        ]);
    }

    public function exportPdf(Request $request)
{
    $reportType = $request->query('report_type', 'summary');
    $startYear = (int) $request->query('start_year', date('Y'));
    $endYear = (int) $request->query('end_year', $startYear);

    $selectedDistricts = array_map(
        'intval',
        $request->query('districts', [])
    );

    $allDistricts = $this->getAllDistrictsData($startYear, $endYear);

    $filteredDistricts = count($selectedDistricts) > 0
        ? array_values(array_filter(
            $allDistricts,
            fn ($d) => in_array($d['id'], $selectedDistricts, true)
        ))
        : $allDistricts;

    $stats = [
        'totalCases' => array_sum(
            array_column($filteredDistricts, 'divorceCount')
        ),
        'highRiskCount' => count(array_filter(
            $filteredDistricts,
            fn ($d) => in_array(
                $d['riskLevel'],
                ['critical', 'high'],
                true
            )
        )),
    ];

    $riskDistribution = collect([
        'critical', 'high', 'moderate', 'low'
    ])->map(function ($level) use ($filteredDistricts) {
        return [
            'label' => riskLabel($level),
            'count' => count(array_filter(
                $filteredDistricts,
                fn ($d) => $d['riskLevel'] === $level
            )),
        ];
    })->all();

    $recommendations = $this->getRecommendations();

    \Carbon\Carbon::setLocale('id');

    $pdf = Pdf::loadView('reports.pdf', [
        'reportType' => $reportType,
        'startYear' => $startYear,
        'endYear' => $endYear,
        'filteredDistricts' => $filteredDistricts,
        'stats' => $stats,
        'riskDistribution' => $riskDistribution,
        'recommendations' => $recommendations,
        'reportDate' => now()->translatedFormat('l, j F Y'),
    ])->setPaper('a4', 'portrait');

    return $pdf->download('laporan-risiko-perceraian.pdf');
}

    public function exportCsv(Request $request): Response
    {
        $startYear = (int) $request->query('start_year', date('Y'));
        $endYear = (int) $request->query('end_year', $startYear);
        $selectedDistricts = array_map('intval', $request->query('districts', []));
        $allDistricts      = $this->getAllDistrictsData($startYear, $endYear);

        $filteredDistricts = count($selectedDistricts) > 0
            ? array_values(array_filter($allDistricts, fn ($d) => in_array($d['id'], $selectedDistricts, true)))
            : $allDistricts;

        $csv  = "Laporan Sistem Pengambilan Keputusan - DP3AKB Kalimantan Selatan\n";
        $csv .= 'Tanggal: ' . now()->translatedFormat('j F Y') . "\n\n";
        $csv .= "Kabupaten,Risiko,Skor,Kasus,Populasi,Tingkat Perceraian\n";

        foreach ($filteredDistricts as $d) {
            $csv .= "{$d['name']},{$d['riskLevel']},{$d['riskScore']},{$d['divorceCount']},{$d['populationCount']},{$d['divorceRate']}\n";
        }

        $filename = 'laporan-risiko-' . now()->format('d-m-Y') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function getAllDistrictsData(int $startYear, int $endYear): array
    {
        $districts = District::orderBy('name')->get();

        $schema = DB::getSchemaBuilder();

        // Deteksi tabel yang tersedia
        // $hasRecaps  = DB::getSchemaBuilder()->hasTable('divorce_recaps');
        // $hasCases   = DB::getSchemaBuilder()->hasTable('divorce_cases');
        // $popCols    = DB::getSchemaBuilder()->hasTable('populations')
        //     ? DB::getSchemaBuilder()->getColumnListing('populations') : [];
        $hasRecaps = $schema->hasTable('divorce_recaps');
    $hasCases = $schema->hasTable('divorce_cases');

    $popCols = $schema->hasTable('populations')
        ? $schema->getColumnListing('populations')
        : [];
        $hasNewPop  = in_array('jumlah_penduduk_ribu', $popCols);

        return $districts->map(function ($district) use ($hasRecaps, $hasCases, $hasNewPop, $schema, $startYear,
        $endYear,) {

            // Hitung kasus perceraian
            // if ($hasRecaps) {
            //     $divorceCount = DB::table('divorce_recaps')
            //         ->where('district_id', $district->id)
            //         ->where('kategori', 'Diputus')
            //         ->whereIn('jenis_perkara', ['Cerai Talak', 'Cerai Gugat'])
            //         ->sum('jumlah');
            // } elseif ($hasCases) {
            //     $divorceCount = DB::table('divorce_cases')
            //         ->where('district_id', $district->id)
            //         ->count();
            // } else {
            //     $divorceCount = 0;
            // }

            // Hitung kasus perceraian code new
            if ($hasRecaps) {
            $recapQuery = DB::table('divorce_recaps')
                ->where('district_id', $district->id)
                ->whereBetween('year', [$startYear, $endYear]);

            $recapColumns = $schema->getColumnListing('divorce_recaps');

            if (in_array('jumlah_diputus', $recapColumns)) {
                $divorceCount = (int) $recapQuery->sum('jumlah_diputus');
            } else {
                $divorceCount = 0;
            }
        } elseif ($hasCases) {
            $divorceCount = DB::table('divorce_cases')
                ->where('district_id', $district->id)
                ->whereBetween('year', [$startYear, $endYear])
                ->count();
        } else {
            $divorceCount = 0;
        }

//             // Hitung populasi
//             if ($hasNewPop) {
//                 // $populationRibu  = (float) DB::table('populations')
//                 //     ->where('district_id', $district->id)
//                 //     ->orderByDesc('year', $startYear)
//                 //     ->value('jumlah_penduduk_ribu') ?? 0;
//                 // $populationCount = (int) ($populationRibu * 1000);
//                 if ($schema->hasTable('populations')) {
//                 $populationCount = 0;
//                 $populationQuery = DB::table('populations')
//                 ->where('district_id', $district->id);

//                 // Jika laporan satu tahun, gunakan tahun tersebut.
//     // Jika rentang beberapa tahun, gunakan tahun akhir.
//     $populationYear = ($startYear === $endYear)
//         ? $startYear
//         : $endYear;

//         $populationRibu = $populationQuery
//         ->where('year', $populationYear)
//         ->value('jumlah_penduduk_ribu');

//     if ($populationRibu !== null) {
//         $populationCount = (int) round(
//             (float) $populationRibu * 1000
//         );
//     }
// }
                
//                 // Ambil populasi sesuai tahun laporan yang dipilih
//             if ($startYear === $endYear) {
//                 $populationRibu = (float) (
//                     (clone $populationQuery)
//                         ->where('year', $startYear)
//                         ->value('jumlah_penduduk_ribu') ?? 0
//                 );

//                 } else {
//                 // Jika rentang beberapa tahun, gunakan populasi pada tahun akhir
//                 $populationRibu = (float) (
//                     (clone $populationQuery)
//                         ->where('year', $endYear)
//                         ->value('jumlah_penduduk_ribu') ?? 0
//                 );

//                 }

//                 $populationCount = (int) ($populationRibu * 1000);

//             } elseif ($schema->hasTable('populations')) {
//                 // $populationCount = (int) DB::table('populations')
//                 //     ->where('district_id', $district->id)
//                 //     ->orderByDesc('year')
//                 //     ->value('count') ?? 0;
//                 // Pertahankan dukungan untuk struktur tabel populasi lama
//             $populationQuery = DB::table('populations')
//                 ->where('district_id', $district->id);

//                 if ($startYear === $endYear) {
//                 $populationCount = (int) (
//                     (clone $populationQuery)
//                         ->where('year', $startYear)
//                         ->value('count') ?? 0
//                 );

//                 } else {
//                 $populationCount = (int) (
//                     (clone $populationQuery)
//                         ->where('year', $endYear)
//                         ->value('count') ?? 0
//                 );
//             }

//             } else {
//                 $populationCount = 0;
//             }

        // Hitung populasi
        $populationCount = 0;
if ($hasNewPop) {

    // Tentukan tahun populasi yang digunakan
    $populationYear = ($startYear === $endYear)
        ? $startYear
        : $endYear;

    $populationRibu = DB::table('populations')
        ->where('district_id', $district->id)
        ->where('year', $populationYear)
        ->value('jumlah_penduduk_ribu');

    if ($populationRibu !== null) {
        $populationCount = (int) round(
            (float) $populationRibu * 1000
        );
    }
} elseif ($schema->hasTable('populations')) {
    // Dukungan untuk struktur tabel populasi lama
    $populationYear = ($startYear === $endYear)
        ? $startYear
        : $endYear;

    $populationCount = (int) (
        DB::table('populations')
            ->where('district_id', $district->id)
            ->where('year', $populationYear)
            ->value('count') ?? 0
    );
} else {
    $populationCount = 0;
}

            // Hitung angka perceraian per 1.000 penduduk
            $divorceRate = $populationCount > 0
                ? round(($divorceCount / $populationCount) * 1000, 1) : 0;

            // Hitung skor risiko        
            $riskScore = min(100, $divorceCount > 0 ? intval($divorceCount / 10) + 20 : 20);
            $riskLevel = match (true) {
                $riskScore >= 80 => 'critical',
                $riskScore >= 60 => 'high',
                $riskScore >= 40 => 'moderate',
                default          => 'low',
            };

            return [
                'id'              => $district->id,
                'name'            => $district->name,
                'riskScore'       => $riskScore,
                'riskLevel'       => $riskLevel,
                'divorceRate'     => $divorceRate,
                'divorceCount'    => (int) $divorceCount,
                // 'divorceCount'    => $divorceCount,
                'populationCount' => $populationCount,
            ];
        })->all();
    }

    private function getRecommendations(): array
    {
        // Ambil dari tabel recommendations kalau ada
        if (DB::getSchemaBuilder()->hasTable('recommendations')) {
            return DB::table('recommendations')
                ->select('program as title', 'description')
                ->take(5)
                ->get()
                ->map(fn ($r) => ['title' => $r->title, 'description' => $r->description])
                ->all();
        }

        return [
            ['title' => 'Mediasi Intensif & Konseling PUSPAGA', 'description' => 'Diprioritaskan untuk kabupaten dengan risiko kritis dan tinggi.'],
            ['title' => 'Program Kesadaran Komunitas', 'description' => 'Kampanye edukasi untuk kabupaten dengan risiko sedang.'],
            ['title' => 'Pendidikan Preventif', 'description' => 'Program berkelanjutan untuk menjaga stabilitas di kabupaten berisiko rendah.'],
        ];
    }
}