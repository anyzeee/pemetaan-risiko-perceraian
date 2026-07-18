@extends('layouts.dashboard')

@section('title', 'Hasil K-Means Clustering - KARSA')

@section('content')

<div class="space-y-6">

    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Hasil K-Means Clustering</h1>
            <p class="mt-2 text-slate-600">Detail proses dan hasil pengelompokan kabupaten berdasarkan risiko perceraian</p>
        </div>
        {{-- Tombol jalankan ulang clustering --}}
        <form method="POST" action="{{ route('clustering-analysis.run') }}">
            @csrf
            <button type="submit"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Jalankan Ulang Clustering
            </button>
        </form>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if (!$hasResults)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-6 text-center">
            <p class="font-semibold text-amber-800">Clustering belum dijalankan</p>
            <p class="mt-1 text-sm text-amber-700">Klik tombol "Jalankan Ulang Clustering" untuk memulai proses K-Means.</p>
        </div>
    @else

        {{-- Info Proses --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold text-slate-500">Jumlah Kabupaten</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $totalDistricts }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold text-slate-500">Jumlah Cluster (k)</p>
                <p class="mt-2 text-3xl font-bold text-blue-600">{{ $totalClusters }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold text-slate-500">Iterasi Konvergensi</p>
                <p class="mt-2 text-3xl font-bold text-green-600">{{ $iterationRan }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold text-slate-500">Terakhir Dijalankan</p>
                <p class="mt-2 text-sm font-bold text-slate-900">{{ $lastRun }}</p>
            </div>
        </div>

        {{-- Fitur yang Digunakan --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6">
            <h3 class="font-semibold text-slate-900 mb-3">Fitur / Variabel yang Digunakan</h3>
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
                @foreach ([
                    ['Jumlah Kasus Perceraian', 'feat_divorce_count', 'bg-red-50 border-red-200 text-red-800'],
                    ['Tingkat Perceraian (per 1000)', 'feat_divorce_rate', 'bg-orange-50 border-orange-200 text-orange-800'],
                    ['Rasio Pernikahan', 'feat_marriage_ratio', 'bg-yellow-50 border-yellow-200 text-yellow-800'],
                    ['Jumlah Populasi', 'feat_population', 'bg-blue-50 border-blue-200 text-blue-800'],
                    ['Bobot Faktor Perceraian', 'feat_factor_weight', 'bg-purple-50 border-purple-200 text-purple-800'],
                ] as $feat)
                    <div class="rounded-lg border p-3 {{ $feat[2] }}">
                        <p class="text-xs font-semibold">{{ $feat[0] }}</p>
                        <p class="mt-1 text-xs opacity-70">Ternormalisasi 0–1</p>
                    </div>
                @endforeach
            </div>
            <p class="mt-3 text-xs text-slate-500">Semua fitur dinormalisasi menggunakan metode Min-Max Normalization sebelum diproses K-Means.</p>
        </div>

        {{-- Hasil per Cluster --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6">
            <h3 class="font-semibold text-slate-900 mb-4">Distribusi Hasil Clustering</h3>
            <div class="grid gap-4 lg:grid-cols-4">
                @foreach ($clusterSummary as $summary)
                    @php
                        $borderBg = match($summary['risk_level']) {
                            'critical' => 'border-red-200 bg-red-50',
                            'high'     => 'border-orange-200 bg-orange-50',
                            'moderate' => 'border-yellow-200 bg-yellow-50',
                            default    => 'border-green-200 bg-green-50',
                        };
                        $textColor = match($summary['risk_level']) {
                            'critical' => 'text-red-700',
                            'high'     => 'text-orange-700',
                            'moderate' => 'text-yellow-700',
                            default    => 'text-green-700',
                        };
                        $levelLabel = match($summary['risk_level']) {
                            'critical' => 'Kritis',
                            'high'     => 'Tinggi',
                            'moderate' => 'Sedang',
                            default    => 'Rendah',
                        };
                    @endphp
                    <div class="rounded-lg border p-4 {{ $borderBg }}">
                        <p class="text-xs font-semibold {{ $textColor }} uppercase">Cluster {{ $summary['cluster_id'] }} — {{ $levelLabel }}</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ $summary['count'] }}</p>
                        <p class="text-xs text-slate-600">kabupaten</p>
                        <div class="mt-2 text-xs text-slate-600 space-y-1">
                            <p>Avg. Skor Risiko: <span class="font-semibold text-slate-800">{{ $summary['avg_risk_score'] }}</span></p>
                            <p>Avg. Tingkat Cerai: <span class="font-semibold text-slate-800">{{ $summary['avg_divorce_rate'] }}</span></p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Tabel Detail Hasil per Kabupaten --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6">
            <h3 class="font-semibold text-slate-900 mb-4">Tabel Hasil Clustering per Kabupaten</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-900">Kabupaten/Kota</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-900">Cluster</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-900">Tingkat Risiko</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-900">Skor Risiko</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Kasus Cerai</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Tingkat Cerai</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Populasi</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Iterasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($results as $result)
                            @php
                                $badgeClass = match($result->risk_level) {
                                    'critical' => 'bg-red-100 text-red-800',
                                    'high'     => 'bg-orange-100 text-orange-800',
                                    'moderate' => 'bg-yellow-100 text-yellow-800',
                                    default    => 'bg-green-100 text-green-800',
                                };
                                $levelLabel = match($result->risk_level) {
                                    'critical' => 'Kritis',
                                    'high'     => 'Tinggi',
                                    'moderate' => 'Sedang',
                                    default    => 'Rendah',
                                };
                            @endphp
                            <tr class="border-b border-slate-100 hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ $result->district->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-center text-slate-700">
                                    C{{ $result->cluster_id }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-block rounded-full px-2.5 py-1 text-xs font-semibold {{ $badgeClass }}">
                                        {{ $levelLabel }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-slate-900">
                                    {{ $result->risk_score }}
                                </td>
                                <td class="px-4 py-3 text-right text-slate-700">
                                    {{ number_format($result->divorce_count, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right text-slate-700">
                                    {{ number_format($result->divorce_rate, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right text-slate-700">
                                    {{ $result->population_count > 0 ? number_format($result->population_count, 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-right text-slate-700">
                                    {{ $result->iteration_ran }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Nilai Fitur Ternormalisasi --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6">
            <h3 class="font-semibold text-slate-900 mb-1">Nilai Fitur Ternormalisasi (Min-Max)</h3>
            <p class="text-xs text-slate-500 mb-4">Nilai 0–1 yang digunakan algoritma K-Means dalam proses clustering</p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-900">Kabupaten/Kota</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-900">Cluster</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Jml. Kasus</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Tingkat Cerai</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Rasio Nikah</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Populasi</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Bobot Faktor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($results as $result)
                            <tr class="border-b border-slate-100 hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $result->district->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-center text-slate-600">C{{ $result->cluster_id }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ number_format($result->feat_divorce_count, 4, ',', '') }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ number_format($result->feat_divorce_rate, 4, ',', '') }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ number_format($result->feat_marriage_ratio, 4, ',', '') }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ number_format($result->feat_population, 4, ',', '') }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ number_format($result->feat_factor_weight, 4, ',', '') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @endif
</div>

@endsection