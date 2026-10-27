@extends('layouts.dashboard')

@section('title', 'Dashboard - Sistem Pengambilan Keputusan DP3AKB')

@section('content')

<div class="space-y-6">

    {{-- Page Header --}}
    <div>
        <h1 class="text-3xl font-bold text-slate-900">Dashboard Eksekutif</h1>
        <p class="mt-2 text-slate-600">Ringkasan analisis risiko perceraian di Kalimantan Selatan</p>
    </div>

    {{-- Executive Summary Cards --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">

        {{-- Total Kasus Perceraian --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-slate-600">Total Kasus Perceraian</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">
                        {{ number_format($stats['totalCases'], 0, ',', '.') }}
                        <span class="ml-2 text-xs font-semibold text-red-600">&uarr; 12%</span>
                    </p>
                    <p class="mt-1 text-xs text-slate-500">Tahun 2024</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Kabupaten Risiko Tertinggi --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-slate-600">Kabupaten Risiko Tertinggi</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">
                        {{ $stats['highestRiskDistrict']['name'] }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">Skor Risiko: {{ $stats['highestRiskDistrict']['riskScore'] }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg {{ $stats['highestRiskDistrict']['riskLevel'] === 'critical' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600' }}">
                    <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Faktor Dominan Perceraian --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-slate-600">Faktor Dominan Perceraian</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">
                        {{ \Illuminate\Support\Str::before($stats['dominantFactor'], ' ') }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">{{ $stats['dominantFactor'] }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm-.5-13h1v6h-1zm0 8h1v2h-1z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Update Data Terakhir --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-slate-600">Update Data Terakhir</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">
                        {{ $tanggalFormatted }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">Data adalah yang terbaru</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-50 text-green-600">
                    <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zm-5.04-6.71l-2.75 3.54h3.5l4-5.08-4-3.24-3.5 4.15.75 1.63z"/>
                    </svg>
                </div>
            </div>
        </div>

    </div>

{{-- Grafik Tren Perceraian --}}
    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <h3 class="text-lg font-semibold text-slate-900">
            Tren Kasus Perceraian yang Diputus
        </h3>
        <p class="mt-1 text-sm text-slate-500">
            Jumlah perkara perceraian yang diputus berdasarkan data yang tersedia di sistem.
        </p>

        <div class="mt-6 h-80">
            <canvas id="divorceTrendChart"></canvas>
        </div>
    </div>

    {{-- Quick Statistics --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Risk Distribution --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6">
            <h3 class="font-semibold text-slate-900">Distribusi Risiko</h3>
            {{-- Diagram Donat Distribusi Risiko --}}
<div class="mx-auto mt-4 h-48 max-w-xs">
    <canvas id="riskDistributionChart"></canvas>
</div>
            <div class="mt-4 space-y-3">
                @foreach ($riskLevels as $rl)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="h-3 w-3 rounded-full {{ $rl['color'] }}"></div>
                            <span class="text-sm text-slate-600">{{ $rl['level'] }}</span>
                        </div>
                        <span class="font-semibold text-slate-900">
                            {{ $districts->where('riskLevel', $rl['key'])->count() }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Top Districts by Risk --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6">
            <h3 class="font-semibold text-slate-900">Kabupaten Berisiko Tinggi</h3>
            <div class="mt-4 space-y-3">
                @foreach ($topDistricts as $district)
                    @php
                        $style = match($district['riskLevel']) {
                            'critical' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                            'high'     => ['bg' => '#fef3c7', 'text' => '#92400e'],
                            default    => ['bg' => '#dcfce7', 'text' => '#166534'],
                        };
                        $label = match($district['riskLevel']) {
                            'critical' => 'Kritis',
                            'high'     => 'Tinggi',
                            'moderate' => 'Sedang',
                            default    => 'Rendah',
                        };
                    @endphp
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">{{ $district['name'] }}</span>
                        <span class="inline-block rounded-full px-2.5 py-1 text-xs font-semibold"
                              style="background-color: {{ $style['bg'] }}; color: {{ $style['text'] }};">
                            {{ $label }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Recommended Actions --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6">
            <h3 class="font-semibold text-slate-900">Tindakan Prioritas</h3>
            <div class="mt-4 space-y-3">
                <div class="rounded-lg bg-red-50 p-3">
                    <p class="text-xs font-semibold text-red-900">Urgent</p>
                    <p class="mt-1 text-xs text-red-800">Intensif mediasi dan konseling PUSPAGA untuk kabupaten kritis</p>
                </div>
                <div class="rounded-lg bg-orange-50 p-3">
                    <p class="text-xs font-semibold text-orange-900">Tinggi</p>
                    <p class="mt-1 text-xs text-orange-800">Kampanye kesadaran komunitas dan mediasi diperkuat</p>
                </div>
                <div class="rounded-lg bg-yellow-50 p-3">
                    <p class="text-xs font-semibold text-yellow-900">Medium</p>
                    <p class="mt-1 text-xs text-yellow-800">Program pencegahan dan keterlibatan komunitas</p>
                </div>
            </div>
        </div>

    </div>

    {{-- Quick Links --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <a href="/data-management" class="rounded-lg border border-slate-200 bg-white p-4 hover:bg-slate-50">
            <div class="font-semibold text-slate-900">Kelola Data</div>
            <p class="mt-1 text-sm text-slate-600">Tambah, edit, atau hapus data kasus</p>
        </a>
        <a href="/risk-mapping" class="rounded-lg border border-slate-200 bg-white p-4 hover:bg-slate-50">
            <div class="font-semibold text-slate-900">Peta Risiko</div>
            <p class="mt-1 text-sm text-slate-600">Visualisasi risiko berdasarkan wilayah</p>
        </a>
        <a href="/clustering-analysis" class="rounded-lg border border-slate-200 bg-white p-4 hover:bg-slate-50">
            <div class="font-semibold text-slate-900">Clustering</div>
            <p class="mt-1 text-sm text-slate-600">Analisis pengelompokan risiko</p>
        </a>
        <a href="/reports" class="rounded-lg border border-slate-200 bg-white p-4 hover:bg-slate-50">
            <div class="font-semibold text-slate-900">Laporan</div>
            <p class="mt-1 text-sm text-slate-600">Buat dan ekspor laporan keputusan</p>
        </a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const trendCanvas = document.getElementById('divorceTrendChart');

    if (trendCanvas) {
        new Chart(trendCanvas, {
            type: 'line',

            data: {
                labels: @json($divorceTrend['labels']),

                datasets: [{
                    label: 'Jumlah Kasus Perceraian yang Diputus',
                    data: @json($divorceTrend['data']),
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.12)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },

                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        title: {
                            display: true,
                            text: 'Jumlah Kasus'
                        }
                    },

                    x: {
                        title: {
                            display: true,
                            text: 'Tahun'
                        }
                    }
                }
            }
        });
    }

// Diagram Donat Distribusi Risiko
const riskCanvas = document.getElementById('riskDistributionChart');

if (riskCanvas) {
    new Chart(riskCanvas, {
        type: 'doughnut',

        data: {
            labels: ['Kritis', 'Tinggi', 'Sedang', 'Rendah'],

            datasets: [{
                label: 'Jumlah Kabupaten/Kota',
                data: [
    {{ $districts->where('riskLevel', 'critical')->count() }},
    {{ $districts->where('riskLevel', 'high')->count() }},
    {{ $districts->where('riskLevel', 'moderate')->count() }},
    {{ $districts->where('riskLevel', 'low')->count() }}
],
                backgroundColor: [
                    '#ef4444',
                    '#f97316',
                    '#eab308',
                    '#22c55e'
                ],
                borderColor: '#ffffff',
                borderWidth: 2,
                hoverOffset: 6
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.raw + ' kabupaten/kota';
                        }
                    }
                }
            },

            cutout: '65%'
        }
    });
}

</script>

@endsection