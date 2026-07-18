<?php

namespace App\Traits;

use App\Models\ClusterResult;
use App\Models\District;
use App\Models\DivorceFactorRecap;
use App\Models\DivorceRecap;
use App\Models\Marriage;
use App\Models\Population;
use Illuminate\Support\Collection;

trait ComputesDistrictRisk
{
    /**
     * Ambil data risiko kabupaten.
     * Prioritas: tabel cluster_results → kalkulasi manual (fallback).
     */
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
            // Jumlah kasus cerai diputus (bukan diterima, bukan jumlah)
            $divorceCount = DivorceRecap::where('district_id', $district->id)
                ->where('kategori', 'Diputus')
                ->whereIn('jenis_perkara', ['Cerai Talak', 'Cerai Gugat'])
                ->sum('jumlah');

            $marriageCount   = Marriage::where('district_id', $district->id)->sum('count') ?? 0;
            $populationRibu  = Population::where('district_id', $district->id)
                                         ->latest('year')->value('jumlah_penduduk_ribu') ?? 0;
            $populationCount = (int) ($populationRibu * 1000);

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
                'marriageCount'   => (int) $marriageCount,
                'populationCount' => $populationCount,
                'clusterId'       => null,
                'mainFactors'     => $this->getMainFactors(),
            ];
        });
    }

    protected function getMainFactors(): array
    {
        // Ambil faktor dengan jumlah kasus terbanyak dari seluruh data
        $factors = DivorceFactorRecap::selectRaw('faktor, SUM(jumlah_kasus) as total')
            ->groupBy('faktor')
            ->orderByDesc('total')
            ->take(3)
            ->pluck('faktor')
            ->toArray();

        return count($factors) > 0 ? $factors : ['Ekonomi', 'Perselisihan'];
    }

    protected function getDominantFactor(): string
    {
        $factor = DivorceFactorRecap::selectRaw('faktor, SUM(jumlah_kasus) as total')
            ->groupBy('faktor')
            ->orderByDesc('total')
            ->first();

        return $factor ? $factor->faktor : 'Ekonomi dan Perselisihan';
    }
}