<?php

namespace App\Services;

use App\Models\ClusterResult;
use App\Models\District;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KMeansClusteringService
{
    private int $k;
    private int $maxIterations;
    private int $seed;

    /**
     * $seed mengunci angka acak pada inisialisasi centroid (K-Means++),
     * sehingga data yang sama selalu menghasilkan pembagian cluster yang sama.
     */
    public function __construct(int $k = 4, int $maxIterations = 100, int $seed = 42)
    {
        $this->k             = $k;
        $this->maxIterations = $maxIterations;
        $this->seed          = $seed;
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
        $riskScores    = $this->computeRiskScores($rawData, $assignments, $clusterLabels);

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

        // Deteksi tabel yang tersedia
        $hasRecaps     = DB::getSchemaBuilder()->hasTable('divorce_recaps');
        $hasFactRecaps = DB::getSchemaBuilder()->hasTable('divorce_factor_recaps');
        $popCols       = DB::getSchemaBuilder()->hasTable('populations')
            ? DB::getSchemaBuilder()->getColumnListing('populations') : [];
        $hasNewPop     = in_array('jumlah_penduduk_ribu', $popCols);

        $avgWeight = $hasFactRecaps
            ? (float) (DB::table('divorce_factor_recaps')->avg('jumlah_kasus') ?? 0)
            : 0;

        return $districts->map(function ($district) use ($avgWeight, $hasRecaps, $hasFactRecaps, $hasNewPop) {

            // Hitung kasus cerai (SUDAH DIPUTUS)
            // Tabel divorce_recaps berformat wide: jumlahkan kolom
            // cerai_talak_diputus + cerai_gugat_diputus per kabupaten.
            if ($hasRecaps) {
                $row = DB::table('divorce_recaps')
                    ->where('district_id', $district->id)
                    ->selectRaw('SUM(cerai_talak_diputus) as talak, SUM(cerai_gugat_diputus) as gugat')
                    ->first();

                $divorceCount = ((int) ($row->talak ?? 0)) + ((int) ($row->gugat ?? 0));
            } elseif (DB::getSchemaBuilder()->hasTable('divorce_cases')) {
                $divorceCount = (int) DB::table('divorce_cases')
                    ->where('district_id', $district->id)->count();
            } else {
                $divorceCount = 0;
            }

            // Hitung pernikahan
            $marriageCount = DB::getSchemaBuilder()->hasTable('marriages')
                ? (int) (DB::table('marriages')->where('district_id', $district->id)->sum('count') ?? 0)
                : 0;

            // Hitung populasi
            if ($hasNewPop) {
                $populationRibu  = (float) (DB::table('populations')
                    ->where('district_id', $district->id)
                    ->orderByDesc('year')->value('jumlah_penduduk_ribu') ?? 0);
                $populationCount = (int) ($populationRibu * 1000);
            } elseif (DB::getSchemaBuilder()->hasTable('populations')) {
                $populationCount = (int) (DB::table('populations')
                    ->where('district_id', $district->id)
                    ->orderByDesc('year')->value('count') ?? 0);
            } else {
                $populationCount = 0;
            }

            $divorceRate   = $populationCount > 0 ? ($divorceCount / $populationCount) * 1000 : 0;
            $marriageRatio = ($populationCount > 0 && $marriageCount > 0)
                ? ($marriageCount / $populationCount) * 1000 : 0;

            // Bobot faktor per kabupaten
            $districtFactor = $hasFactRecaps
                ? (float) (DB::table('divorce_factor_recaps')
                    ->where('district_id', $district->id)->sum('jumlah_kasus') ?? 0)
                : 0;

            return [
                'district_id'     => $district->id,
                'name'            => $district->name,
                'divorce_count'   => $divorceCount,
                'marriage_count'  => $marriageCount,
                'population_count'=> $populationCount,
                'divorce_rate'    => round($divorceRate, 4),
                'marriage_ratio'  => round($marriageRatio, 4),
                'factor_weight'   => round($districtFactor, 4),
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
                $range             = $max[$feat] - $min[$feat];
                $normalized[$feat] = $range > 0
                    ? round(($row[$feat] - $min[$feat]) / $range, 6) : 0;
            }
            return $normalized;
        })->all();
    }

    private function kmeans(array $normalized): array
    {
        $features    = ['divorce_count', 'divorce_rate', 'marriage_ratio', 'population_count', 'factor_weight'];
        $n           = count($normalized);

        // Kunci angka acak supaya hasil clustering konsisten setiap dijalankan.
        mt_srand($this->seed);

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

        // Cegah cluster kosong: kalau ada cluster tanpa anggota (bisa terjadi
        // pada dataset kecil), pindahkan titik paling jauh dari centroid-nya
        // sendiri (dari cluster yang anggotanya >1) ke cluster kosong tersebut.
        $this->fixEmptyClusters($normalized, $centroids, $assignments, $features);

        return [$assignments, $centroids, $iterations];
    }

    /**
     * Menangani cluster kosong hasil K-Means. Untuk setiap cluster yang tidak
     * punya anggota, cari titik dengan jarak terjauh dari centroid cluster
     * asalnya (di antara cluster yang anggotanya lebih dari 1), lalu pindahkan
     * titik itu menjadi anggota (dan centroid awal) cluster yang kosong.
     */
    private function fixEmptyClusters(array $normalized, array &$centroids, array &$assignments, array $features): void
    {
        for ($cId = 0; $cId < $this->k; $cId++) {
            $members = array_keys(array_filter($assignments, fn ($a) => $a === $cId));
            if (!empty($members)) continue;

            $farthestIdx  = null;
            $farthestDist = -1;

            foreach ($normalized as $idx => $point) {
                $ownCluster = $assignments[$idx];
                $ownMembers = array_keys(array_filter($assignments, fn ($a) => $a === $ownCluster));

                // Jangan ambil dari cluster yang anggotanya cuma 1,
                // supaya tidak menciptakan cluster kosong baru.
                if (count($ownMembers) <= 1) continue;

                $dist = $this->euclideanDistance($point, $centroids[$ownCluster] ?? [], $features);
                if ($dist > $farthestDist) {
                    $farthestDist = $dist;
                    $farthestIdx  = $idx;
                }
            }

            if ($farthestIdx !== null) {
                $assignments[$farthestIdx] = $cId;
                foreach ($features as $feat) {
                    $centroids[$cId][$feat] = $normalized[$farthestIdx][$feat];
                }
            }
        }
    }

    private function initCentroids(array $normalized, array $features): array
    {
        $centroids = [$normalized[0]];

        for ($k = 1; $k < $this->k; $k++) {
            $distances = [];
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

    /**
     * Skor risiko (1-100) berdasarkan tingkat risiko cluster dan tingkat cerai.
     *
     * Aturan:
     *  1. Kabupaten di cluster yang lebih berisiko selalu mendapat skor lebih tinggi
     *     (Kritis 76-100, Tinggi 51-75, Sedang 26-50, Rendah 1-25).
     *  2. Di dalam satu cluster, tingkat cerai (per 1.000 penduduk) yang lebih tinggi
     *     mendapat skor lebih tinggi.
     *
     * Dengan begitu, mengurutkan skor dari terbesar = mengurutkan dari cluster
     * paling berisiko, lalu dari tingkat cerai tertinggi.
     */
    private function computeRiskScores(Collection $rawData, array $assignments, array $clusterLabels): array
    {
        $bandBase = ['critical' => 75, 'high' => 50, 'moderate' => 25, 'low' => 0];

        // Kumpulkan nilai tingkat cerai unik per cluster, diurutkan dari terkecil.
        // Peringkat (bukan selisih nilai) dipakai supaya dua kabupaten dengan
        // tingkat cerai berdekatan tidak mendapat skor bulat yang sama.
        $uniqueRatesByCluster = [];
        foreach ($assignments as $idx => $clusterId) {
            $uniqueRatesByCluster[$clusterId][] = (string) (float) $rawData[$idx]['divorce_rate'];
        }
        foreach ($uniqueRatesByCluster as $clusterId => $rates) {
            $rates = array_values(array_unique($rates));
            usort($rates, fn ($a, $b) => (float) $a <=> (float) $b);
            $uniqueRatesByCluster[$clusterId] = $rates;
        }

        $scores = [];
        foreach ($assignments as $idx => $clusterId) {
            $label = $clusterLabels[$clusterId] ?? 'low';
            $base  = $bandBase[$label] ?? 0;

            $rates = $uniqueRatesByCluster[$clusterId];
            $rank  = array_search((string) (float) $rawData[$idx]['divorce_rate'], $rates, true);
            $count = count($rates);

            // Posisi 0-1 di dalam cluster; cluster dengan satu nilai saja -> tengah
            $position = ($count > 1 && $rank !== false) ? $rank / ($count - 1) : 0.5;

            $scores[$idx] = (int) ($base + 1 + round($position * 24));
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