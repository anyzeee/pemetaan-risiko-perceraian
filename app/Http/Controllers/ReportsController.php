<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\DivorceCase;
use App\Models\Population;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        $reportType         = $request->query('report_type', 'summary');
        $startYear          = (int) $request->query('start_year', date('Y'));
        $endYear            = (int) $request->query('end_year', date('Y'));
        $selectedDistricts  = array_map('intval', $request->query('districts', []));
        $showPreview        = $request->has('show_preview') ? (bool) $request->query('show_preview') : true;

        $allDistricts = $this->getAllDistrictsData();

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

    public function exportCsv(Request $request): Response
    {
        $selectedDistricts = array_map('intval', $request->query('districts', []));

        $allDistricts = $this->getAllDistrictsData();

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

    /**
     * Susun data risiko per kabupaten.
     * TODO: ganti perhitungan riskScore/riskLevel ini dengan hasil K-Means clustering asli.
     */
    private function getAllDistrictsData(): array
    {
        $districts = District::orderBy('name')->get();

        return $districts->map(function ($district) {
            $divorceCount    = DivorceCase::where('district_id', $district->id)->count();
            $populationCount = Population::where('district_id', $district->id)->latest('year')->value('count') ?? 0;

            $divorceRate = $populationCount > 0
                ? round(($divorceCount / $populationCount) * 1000, 1)
                : 0;

            $riskScore = min(100, $divorceCount * 5 + 20);

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
                'divorceCount'    => $divorceCount,
                'populationCount' => $populationCount,
            ];
        })->all();
    }

    /**
     * Rekomendasi strategi.
     * TODO: ganti dengan data rekomendasi asli (mis. dari tabel recommendations).
     */
    private function getRecommendations(): array
    {
        return [
            [
                'title'       => 'Mediasi Intensif & Konseling PUSPAGA',
                'description' => 'Diprioritaskan untuk kabupaten dengan risiko kritis dan tinggi.',
            ],
            [
                'title'       => 'Program Kesadaran Komunitas',
                'description' => 'Kampanye edukasi untuk kabupaten dengan risiko sedang.',
            ],
            [
                'title'       => 'Pendidikan Preventif',
                'description' => 'Program berkelanjutan untuk menjaga stabilitas di kabupaten berisiko rendah.',
            ],
        ];
    }
}