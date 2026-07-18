<?php

namespace App\Http\Controllers;

use App\Models\ClusterResult;
use App\Services\KMeansClusteringService;
use App\Traits\ComputesDistrictRisk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClusteringController extends Controller
{
    use ComputesDistrictRisk;

    /**
     * Halaman Analisis Clustering (tampilan cluster untuk semua user).
     */
    public function index(Request $request): View
    {
        $expandedCluster = $request->query('expand', 'high-risk');
        $districts       = $this->getDistrictsWithRisk();

        $highRiskDistricts   = $districts->whereIn('riskLevel', ['critical'])->values();
        $mediumRiskDistricts = $districts->whereIn('riskLevel', ['high', 'moderate'])->values();
        $lowRiskDistricts    = $districts->where('riskLevel', 'low')->values();

        $clusters = [
            [
                'id'       => 'high-risk',
                'title'    => 'Cluster Risiko Tinggi',
                'data'     => [
                    'averageRiskScore' => $highRiskDistricts->count() > 0 ? round($highRiskDistricts->avg('riskScore')) : 0,
                    'characteristics'  => ['Tingkat kasus perceraian tinggi', 'Faktor ekonomi dan KDRT dominan', 'Akses layanan konseling terbatas'],
                    'mainFactors'      => $this->getMainFactors(),
                    'districts'        => $highRiskDistricts->map(fn ($d) => ['id' => $d['id'], 'name' => $d['name'], 'riskScore' => $d['riskScore'], 'divorceCount' => $d['divorceCount']])->all(),
                ],
                'color'    => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'icon' => 'bg-red-100 text-red-600'],
                'priority' => 'Urgent',
            ],
            [
                'id'       => 'medium-risk',
                'title'    => 'Cluster Risiko Sedang',
                'data'     => [
                    'averageRiskScore' => $mediumRiskDistricts->count() > 0 ? round($mediumRiskDistricts->avg('riskScore')) : 0,
                    'characteristics'  => ['Kasus perceraian moderat', 'Faktor campuran ekonomi dan komunikasi'],
                    'mainFactors'      => ['Komunikasi', 'Ekonomi'],
                    'districts'        => $mediumRiskDistricts->map(fn ($d) => ['id' => $d['id'], 'name' => $d['name'], 'riskScore' => $d['riskScore'], 'divorceCount' => $d['divorceCount']])->all(),
                ],
                'color'    => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-200', 'icon' => 'bg-yellow-100 text-yellow-600'],
                'priority' => 'High',
            ],
            [
                'id'       => 'low-risk',
                'title'    => 'Cluster Risiko Rendah',
                'data'     => [
                    'averageRiskScore' => $lowRiskDistricts->count() > 0 ? round($lowRiskDistricts->avg('riskScore')) : 0,
                    'characteristics'  => ['Tingkat kasus perceraian rendah', 'Kohesi sosial dan keluarga relatif baik'],
                    'mainFactors'      => ['Stabil', 'Dukungan Keluarga'],
                    'districts'        => $lowRiskDistricts->map(fn ($d) => ['id' => $d['id'], 'name' => $d['name'], 'riskScore' => $d['riskScore'], 'divorceCount' => $d['divorceCount']])->all(),
                ],
                'color'    => ['bg' => 'bg-green-50', 'border' => 'border-green-200', 'icon' => 'bg-green-100 text-green-600'],
                'priority' => 'Medium',
            ],
        ];

        $actionsByCluster = [
            'high-risk'   => [
                ['title' => '1. Mediasi Intensif & Konseling PUSPAGA', 'priority' => 'URGENT'],
                ['title' => '2. Program Pencegahan KDRT', 'priority' => 'URGENT'],
                ['title' => '3. Intervensi Segera', 'priority' => 'URGENT'],
            ],
            'medium-risk' => [
                ['title' => '1. Monitoring & Konseling Berkala', 'priority' => 'HIGH'],
                ['title' => '2. Program Kesadaran Komunitas', 'priority' => 'HIGH'],
            ],
            'low-risk'    => [
                ['title' => '1. Program Pendidikan Preventif', 'priority' => 'MEDIUM'],
                ['title' => '2. Keterlibatan Komunitas', 'priority' => 'MEDIUM'],
            ],
        ];

        return view('clustering-analysis.index', [
            'clusters'         => $clusters,
            'actionsByCluster' => $actionsByCluster,
            'expandedCluster'  => $expandedCluster,
        ]);
    }

    /**
     * Halaman detail hasil K-Means (khusus Admin).
     */
    public function showResults(): View
    {
        $results    = ClusterResult::with('district')->orderBy('cluster_id')->get();
        $hasResults = $results->isNotEmpty();

        $totalDistricts = $results->count();
        $totalClusters  = $results->pluck('cluster_id')->unique()->count();
        $iterationRan   = $results->first()?->iteration_ran ?? 0;
        $lastRun        = $results->first()?->updated_at
            ? $results->first()->updated_at->translatedFormat('j F Y, H:i')
            : '-';

        // Ringkasan per cluster
        $clusterSummary = $results->groupBy('cluster_id')->map(function ($group) {
            return [
                'cluster_id'       => $group->first()->cluster_id,
                'risk_level'       => $group->first()->risk_level,
                'count'            => $group->count(),
                'avg_risk_score'   => round($group->avg('risk_score')),
                'avg_divorce_rate' => number_format($group->avg('divorce_rate'), 2, ',', '.'),
            ];
        })->sortBy('cluster_id')->values()->all();

        return view('clustering-analysis.results', [
            'results'        => $results,
            'hasResults'     => $hasResults,
            'totalDistricts' => $totalDistricts,
            'totalClusters'  => $totalClusters,
            'iterationRan'   => $iterationRan,
            'lastRun'        => $lastRun,
            'clusterSummary' => $clusterSummary,
        ]);
    }

    /**
     * Jalankan ulang K-Means Clustering dari web (khusus Admin).
     */
    public function runClustering(): RedirectResponse
    {
        $service = new KMeansClusteringService(4, 100);
        $result  = $service->run();

        if (isset($result['error'])) {
            return redirect()->route('clustering-analysis.results')
                ->with('error', $result['error']);
        }

        return redirect()->route('clustering-analysis.results')
            ->with('success', "Clustering selesai! {$result['districts']} kabupaten dikelompokkan dalam {$result['clusters']} cluster setelah {$result['iterations']} iterasi.");
    }
}