<?php

namespace App\Http\Controllers;

use App\Models\DivorceCase;
use App\Traits\ComputesDistrictRisk;
use Illuminate\View\View;
use Carbon\Carbon;

class DashboardController extends Controller
{
    use ComputesDistrictRisk;

    public function index(): View
    {
        $districts = $this->getDistrictsWithRisk();

        $highestRiskDistrict = $districts->sortByDesc('riskScore')->first()
            ?? ['name' => 'N/A', 'riskScore' => 0, 'riskLevel' => 'low'];

        $stats = [
            'totalCases'          => $districts->sum('divorceCount'),
            'highestRiskDistrict' => $highestRiskDistrict,
            'dominantFactor'      => $this->getDominantFactor(),
            'lastUpdate'          => now()->format('Y-m-d'),
        ];

        // Data grafik tren perceraian tahun 2021–2025
        $years = range(2021, 2025);

        $yearlyCases = DivorceCase::query()
            // ->whereIn('year', $years)
            ->selectRaw('year, SUM(jumlah_diputus) as total')
            ->groupBy('year')
            ->orderBy('year')
            ->pluck('total', 'year');

        // $divorceTrend = [
        //     'labels' => $years,
        //     'data' => array_map(
        //         fn ($year) => (int) ($yearlyCases[$year] ?? 0),
        //         $years
        //     ),
        // ];

        $divorceTrend = [
    'labels' => $yearlyCases->keys()
        ->map(fn ($year) => (int) $year)
        ->values()
        ->all(),

    'data' => $yearlyCases->values()
        ->map(fn ($total) => (int) $total)
        ->all(),
];

        $riskLevels = [
            ['level' => 'Kritis',  'key' => 'critical', 'color' => 'bg-red-500'],
            ['level' => 'Tinggi',  'key' => 'high',     'color' => 'bg-orange-500'],
            ['level' => 'Sedang',  'key' => 'moderate', 'color' => 'bg-yellow-500'],
            ['level' => 'Rendah',  'key' => 'low',      'color' => 'bg-green-500'],
        ];

        $topDistricts = $districts->sortByDesc('riskScore')->take(5);

        // Format tanggal Indonesia
        \Carbon\Carbon::setLocale('id');
        $tanggalFormatted = \Carbon\Carbon::parse($stats['lastUpdate'])->translatedFormat('j F Y');

        return view('dashboard.index', [
            'stats'             => $stats,
            'districts'         => $districts,
            'riskLevels'        => $riskLevels,
            'topDistricts'      => $topDistricts,
            'tanggalFormatted'  => $tanggalFormatted,
            'divorceTrend' => $divorceTrend,
        ]);
    }
}