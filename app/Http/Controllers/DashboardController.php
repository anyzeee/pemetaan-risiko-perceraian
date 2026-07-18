<?php

namespace App\Http\Controllers;

use App\Models\DivorceCase;
use App\Traits\ComputesDistrictRisk;
use Illuminate\View\View;

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
        ]);
    }
}