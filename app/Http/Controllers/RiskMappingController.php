<?php

namespace App\Http\Controllers;

use App\Traits\ComputesDistrictRisk;
use Illuminate\View\View;

class RiskMappingController extends Controller
{
    use ComputesDistrictRisk;

    public function index(): View
    {
        $districts = $this->getDistrictsWithRisk();

        $total = max($districts->count(), 1);

        $riskStats = collect(['critical', 'high', 'moderate', 'low'])->map(function ($level) use ($districts, $total) {
            $count = $districts->where('riskLevel', $level)->count();
            return [
                'level'      => riskLabel($level),
                'count'      => $count,
                'percentage' => round(($count / $total) * 100),
            ];
        })->all();

        return view('risk-mapping.index', [
            'districts' => $districts->values()->all(),
            'riskStats' => $riskStats,
        ]);
    }
}