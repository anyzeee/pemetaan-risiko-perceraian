<?php

namespace App\Services;

use App\Models\ClusterResult;
use App\Models\District;
use App\Models\DivorceFactorRecap;
use App\Models\DivorceRecap;
use App\Models\Marriage;
use App\Models\Population;
use Illuminate\Support\Collection;

class KMeansClusteringService
{
    private int $k;
    private int $maxIterations;

    public function __construct(int $k = 4, int $maxIterations = 100)
    {
        $this->k             = $k;
        $this->maxIterations = $maxIterations;
    }

    public function run(): array
    {
        $rawData = $this->collectRawData();

        if ($rawData->isEmpty()) {
            return ['error' => 'Tidak ada data kabupaten. Import data terlebih dahulu.'];
        }

        $normalized    = $this->normalize($rawData);
        [$assignments, $centroids, $iterations] = $this->kmeans($normalized);
        $clusterLabels = $this->assignRiskLabels($assignments, $rawData);
        $riskScores    = $this->computeRiskScores($normalized, $centroids, $assignments, $clusterLabels);

        $this->saveResults($rawData, $normalized, $assignments, $clusterLabels, $riskScores, $iterations);

        return [
            'districts'  => $rawData->count(),
            'clusters'   => $this->k,
            'iterations' => $iterations,
            'labels'     => $clusterLabels,
        ];
    }

    private function collectRawData(): Collection
    {
        $districts = District::orderBy('name')->get();

        // Rata-rata bobot faktor dari total kasus seluruh faktor
        $totalCases  = DivorceFactorRecap::sum('jumlah_kasus') ?: 1;
        $avgWeight   = DivorceFactorRecap::selectRaw('faktor, SUM(jumlah_kasus) as total')
            ->groupBy('faktor')->get()->avg('total') ?? 0;

        return $districts->map(function ($district) use ($avgWeight) {
            // Kasus cerai = Diputus (Cerai Talak + Cerai Gugat), semua tahun
            $divorceCount = DivorceRecap::where('district_id', $district->id)
                ->where('kategori', 'Diputus')
                ->whereIn('jenis_perkara', ['Cerai Talak', 'Cerai Gugat'])
                ->sum('jumlah');

            $marriageCount   = Marriage::where('district_id', $district->id)->sum('count') ?? 0;
            $populationRibu  = Population::where('district_id', $district->id)
                                         ->latest('year')->value('jumlah_penduduk_ribu') ?? 0;
            $populationCount = (int) ($populationRibu * 1000);

            $divorceRate   = $populationCount > 0 ? ($divorceCount / $populationCount) * 1000 : 0;
            $marriageRatio = ($populationCount > 0 && $marriageCount > 0)
                ? ($marriageCount / $populationCount) * 1000 : 0;

            // Bobot faktor spesifik kabupaten ini
            $districtFactor = DivorceFactorRecap::where('district_id', $district->id)
                ->selectRaw('SUM(jumlah_kasus) as total')->first()?->total ?? 0;

            return [
                'district_id'     => $district->id,
                'name'            => $district->name,
                'divorce_count'   => (int) $divorceCount,
                'marriage_count'  => (int) $marriageCount,
                'population_count'=> $populationCount,
                'divorce_rate'    => round($divorceRate, 4),
                'marriage_ratio'  => round($marriageRatio, 4),
                'factor_weight'   => round((float) $districtFactor, 4),
            ];
        });
    }

    private function normalize(Collection $rawData): array
    {
        $features = ['divorce_count', 'divorce_rate', 'marriage_ratio', 'population_count', 'factor_weight'];
        $min = [];
        $max = [];

        foreach ($features as $feat) {
            $values     = $rawData->pluck($feat)->all();
            $min[$feat] = min($values);
            $max[$feat] = max($values);
        }

        return $rawData->map(function ($row) use ($features, $min, $max) {
            $normalized = ['district_id' => $row['district_id']];
            foreach ($features as $feat) {
                $range                = $max[$feat] - $min[$feat];
                $normalized[$feat]    = $range > 0
                    ? round(($row[$feat] - $min[$feat]) / $range, 6) : 0;
            }
            return $normalized;
        })->all();
    }

    private function kmeans(array $normalized): array
    {
        $features    = ['divorce_count', 'divorce_rate', 'marriage_ratio', 'population_count', 'factor_weight'];
        $n           = count($normalized);
        $centroids   = $this->initCentroids($normalized, $features);
        $assignments = array_fill(0, $n, 0);
        $iterations  = 0;

        for ($iter = 0; $iter < $this->maxIterations; $iter++) {
            $iterations++;
            $newAssignments = [];

            foreach ($normalized as $idx => $point) {
                $minDist     = PHP_FLOAT_MAX;
                $bestCluster = 0;
                foreach ($centroids as $cId => $centroid) {
                    $dist = $this->euclideanDistance($point, $centroid, $features);
                    if ($dist < $minDist) { $minDist = $dist; $bestCluster = $cId; }
                }
                $newAssignments[$idx] = $bestCluster;
            }

            if ($newAssignments === $assignments) break;
            $assignments = $newAssignments;

            foreach ($centroids as $cId => &$centroid) {
                $members = array_keys(array_filter($assignments, fn ($a) => $a === $cId));
                if (empty($members)) continue;
                foreach ($features as $feat) {
                    $centroid[$feat] = array_sum(
                        array_map(fn ($idx) => $normalized[$idx][$feat], $members)
                    ) / count($members);
                }
            }
        }

        return [$assignments, $centroids, $iterations];
    }

    private function initCentroids(array $normalized, array $features): array
    {
        $centroids    = [$normalized[0]];

        for ($k = 1; $k < $this->k; $k++) {
            $distances  = [];
            foreach ($normalized as $idx => $point) {
                $minDist = PHP_FLOAT_MAX;
                foreach ($centroids as $centroid) {
                    $dist = $this->euclideanDistance($point, $centroid, $features);
                    if ($dist < $minDist) $minDist = $dist;
                }
                $distances[$idx] = $minDist ** 2;
            }

            $totalDist  = array_sum($distances);
            $threshold  = $totalDist > 0 ? (mt_rand() / mt_getrandmax()) * $totalDist : 0;
            $cumulative = 0;
            $chosen     = array_key_last($normalized);

            foreach ($distances as $idx => $dist) {
                $cumulative += $dist;
                if ($cumulative >= $threshold) { $chosen = $idx; break; }
            }

            $centroids[$k] = $normalized[$chosen];
        }

        return $centroids;
    }

    private function euclideanDistance(array $a, array $b, array $features): float
    {
        $sum = 0;
        foreach ($features as $feat) {
            $sum += (($a[$feat] ?? 0) - ($b[$feat] ?? 0)) ** 2;
        }
        return sqrt($sum);
    }

    private function assignRiskLabels(array $assignments, Collection $rawData): array
    {
        $clusterRates = [];
        foreach ($assignments as $idx => $clusterId) {
            $clusterRates[$clusterId][] = $rawData[$idx]['divorce_rate'];
        }

        $avgRates = [];
        foreach ($clusterRates as $clusterId => $rates) {
            $avgRates[$clusterId] = array_sum($rates) / count($rates);
        }

        arsort($avgRates);
        $sortedClusterIds = array_keys($avgRates);
        $riskLevels       = ['critical', 'high', 'moderate', 'low'];
        $labels           = [];

        foreach ($sortedClusterIds as $rank => $clusterId) {
            $labels[$clusterId] = $riskLevels[$rank] ?? 'low';
        }

        return $labels;
    }

    private function computeRiskScores(array $normalized, array $centroids, array $assignments, array $clusterLabels): array
    {
        $features          = ['divorce_count', 'divorce_rate', 'marriage_ratio', 'population_count', 'factor_weight'];
        $criticalClusterId = array_search('critical', $clusterLabels);
        $criticalCentroid  = $centroids[$criticalClusterId] ?? $centroids[0];
        $maxDist           = sqrt(count($features));

        $scores = [];
        foreach ($normalized as $idx => $point) {
            $dist           = $this->euclideanDistance($point, $criticalCentroid, $features);
            $score          = (1 - ($dist / $maxDist)) * 100;
            $scores[$idx]   = (int) round(max(0, min(100, $score)));
        }

        return $scores;
    }

    private function saveResults(
        Collection $rawData,
        array $normalized,
        array $assignments,
        array $clusterLabels,
        array $riskScores,
        int $iterations
    ): void {
        ClusterResult::truncate();

        foreach ($rawData as $idx => $row) {
            $clusterId = $assignments[$idx];
            $norm      = $normalized[$idx];

            ClusterResult::create([
                'district_id'         => $row['district_id'],
                'cluster_id'          => $clusterId,
                'risk_level'          => $clusterLabels[$clusterId] ?? 'low',
                'risk_score'          => $riskScores[$idx],
                'divorce_rate'        => $row['divorce_rate'],
                'divorce_count'       => $row['divorce_count'],
                'marriage_count'      => $row['marriage_count'],
                'population_count'    => $row['population_count'],
                'factor_weight'       => $row['factor_weight'],
                'feat_divorce_rate'   => $norm['divorce_rate'],
                'feat_divorce_count'  => $norm['divorce_count'],
                'feat_marriage_ratio' => $norm['marriage_ratio'],
                'feat_population'     => $norm['population_count'],
                'feat_factor_weight'  => $norm['factor_weight'],
                'iteration_ran'       => $iterations,
            ]);
        }
    }
}