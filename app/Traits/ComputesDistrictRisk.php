<?php

namespace App\Traits;

use App\Models\ClusterResult;
use App\Models\District;
use App\Models\Marriage;
use App\Models\Population;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait ComputesDistrictRisk
{
    protected function getDistrictsWithRisk(): Collection
    {
        return ClusterResult::exists()
            ? $this->getFromClusterResults()
            : $this->getFromManualCalculation();
    }

    private function getFromClusterResults(): Collection
    {
        return ClusterResult::with('district')->get()->map(function ($result) {
            return [
                'id'              => $result->district_id,
                'name'            => $result->district->name ?? '-',
                'riskScore'       => $result->risk_score,
                'riskLevel'       => $result->risk_level,
                'divorceRate'     => $result->divorce_rate,
                'divorceCount'    => $result->divorce_count,
                'marriageCount'   => $result->marriage_count,
                'populationCount' => $result->population_count,
                'clusterId'       => $result->cluster_id,
                'mainFactors'     => $this->getMainFactors(),
            ];
        });
    }

    private function getFromManualCalculation(): Collection
    {
        $districts = District::orderBy('name')->get();

        return $districts->map(function ($district) {
            // Cek tabel mana yang tersedia
            $hasDivorceRecaps = $this->tableExists('divorce_recaps');
            $hasPopulations   = $this->tableExists('populations');
            $hasMarriages     = $this->tableExists('marriages');

            if ($hasDivorceRecaps) {
                // Tabel divorce_recaps berformat wide: jumlah kasus yang SUDAH DIPUTUS
                // dihitung dari kolom cerai_talak_diputus + cerai_gugat_diputus.
                $row = DB::table('divorce_recaps')
                    ->where('district_id', $district->id)
                    ->selectRaw('SUM(cerai_talak_diputus) as talak, SUM(cerai_gugat_diputus) as gugat')
                    ->first();

                $divorceCount = ((int) ($row->talak ?? 0)) + ((int) ($row->gugat ?? 0));
            } elseif ($this->tableExists('divorce_cases')) {
                $divorceCount = DB::table('divorce_cases')
                    ->where('district_id', $district->id)
                    ->count();
            } else {
                $divorceCount = 0;
            }

            if ($hasMarriages) {
                $marriageCount = (int) DB::table('marriages')
                    ->where('district_id', $district->id)
                    ->sum('count');
            } else {
                $marriageCount = 0;
            }

            if ($hasPopulations) {
                // Cek apakah kolom jumlah_penduduk_ribu ada (redesign) atau count (lama)
                $cols = DB::getSchemaBuilder()->getColumnListing('populations');
                if (in_array('jumlah_penduduk_ribu', $cols)) {
                    $populationRibu  = (float) DB::table('populations')
                        ->where('district_id', $district->id)
                        ->orderByDesc('year')
                        ->value('jumlah_penduduk_ribu') ?? 0;
                    $populationCount = (int) ($populationRibu * 1000);
                } else {
                    $populationCount = (int) DB::table('populations')
                        ->where('district_id', $district->id)
                        ->orderByDesc('year')
                        ->value('count') ?? 0;
                }
            } else {
                $populationCount = 0;
            }

            $divorceRate = $populationCount > 0
                ? round(($divorceCount / $populationCount) * 1000, 4)
                : 0;

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
                'marriageCount'   => $marriageCount,
                'populationCount' => $populationCount,
                'clusterId'       => null,
                'mainFactors'     => $this->getMainFactors(),
            ];
        });
    }

    protected function getMainFactors(): array
    {
        // Cek tabel mana yang tersedia
        if ($this->tableExists('divorce_factor_recaps')) {
            $factors = DB::table('divorce_factor_recaps')
                ->selectRaw('faktor, SUM(jumlah_kasus) as total')
                ->groupBy('faktor')
                ->orderByDesc('total')
                ->take(3)
                ->pluck('faktor')
                ->toArray();
        } elseif ($this->tableExists('divorce_factors')) {
            $factors = DB::table('divorce_factors')
                ->orderByDesc('weight')
                ->take(3)
                ->pluck('name')
                ->toArray();
        } else {
            $factors = [];
        }

        return count($factors) > 0 ? $factors : ['Ekonomi', 'Perselisihan'];
    }

    protected function getDominantFactor(): string
    {
        if ($this->tableExists('divorce_factor_recaps')) {
            $factor = DB::table('divorce_factor_recaps')
                ->selectRaw('faktor, SUM(jumlah_kasus) as total')
                ->groupBy('faktor')
                ->orderByDesc('total')
                ->first();
            return $factor ? $factor->faktor : 'Ekonomi dan Perselisihan';
        }

        if ($this->tableExists('divorce_factors')) {
            $factor = DB::table('divorce_factors')->orderByDesc('weight')->first();
            return $factor ? $factor->name : 'Ekonomi dan Perselisihan';
        }

        return 'Ekonomi dan Perselisihan';
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}