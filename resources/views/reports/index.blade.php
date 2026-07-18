@extends('layouts.dashboard')

@section('title', 'Laporan Analisis - KARSA')

@section('content')

<div class="space-y-6">

    <div>
        <h1 class="text-3xl font-bold text-slate-900">Laporan Analisis</h1>
        <p class="mt-2 text-slate-600">Buat dan ekspor laporan pengambilan keputusan tentang risiko perceraian</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-4">

        {{-- Report Configuration --}}
        <div class="lg:col-span-1 space-y-4 print:hidden">
            <form method="GET" action="{{ route('reports.index') }}" class="rounded-lg border border-slate-200 bg-white p-4">
                <h3 class="mb-3 font-semibold text-slate-900">Konfigurasi Laporan</h3>

                {{-- Report Type --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Tipe Laporan</label>
                    <select name="report_type" onchange="this.form.submit()" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        <option value="summary" @selected($reportType === 'summary')>Ringkasan</option>
                        <option value="detailed" @selected($reportType === 'detailed')>Rinci</option>
                    </select>
                </div>

                {{-- Date Range --}}
                <div class="mb-4 space-y-2">
                    <label class="block text-sm font-medium text-slate-700">Rentang Tahun</label>
                    <input
                        type="number"
                        name="start_year"
                        value="{{ $startYear }}"
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                        placeholder="Tahun mulai"
                    >
                    <!-- <input
                        type="number"
                        name="end_year"
                        value="{{ $endYear }}"
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                        placeholder="Tahun akhir"
                    > -->
                </div>

                {{-- District Filter --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Filter Kabupaten</label>
                    <div class="max-h-48 space-y-1 overflow-y-auto border border-slate-300 rounded p-2">
                        @foreach ($allDistricts as $d)
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    name="districts[]"
                                    value="{{ $d['id'] }}"
                                    {{ in_array($d['id'], $selectedDistricts) ? 'checked' : '' }}
                                    onchange="this.form.submit()"
                                    class="h-4 w-4 rounded border-slate-300"
                                >
                                <span class="text-slate-700">{{ $d['name'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="w-full rounded bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    Terapkan Filter
                </button>

                {{-- Export Buttons --}}
                <div class="space-y-2 border-t border-slate-200 pt-4 mt-4">
                    <button type="button" onclick="window.print()" class="w-full rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Unduh sebagai PDF
                    </button>
                    <a
                        href="{{ route('reports.export-csv', request()->query()) }}"
                        class="block w-full rounded border border-slate-300 bg-white px-4 py-2 text-center text-sm font-medium text-slate-700 hover:bg-slate-50"
                    >
                        Unduh sebagai CSV
                    </a>
                    <button type="button" onclick="window.print()" class="w-full rounded border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Cetak
                    </button>
                </div>

                {{-- Preview Toggle --}}
                <div class="mt-4 border-t border-slate-200 pt-4">
                    <label class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            name="show_preview"
                            value="1"
                            {{ $showPreview ? 'checked' : '' }}
                            onchange="this.form.submit()"
                            class="h-4 w-4 rounded border-slate-300"
                        >
                        <span class="text-sm text-slate-700">Tampilkan pratinjau</span>
                    </label>
                </div>
            </form>
        </div>

        {{-- Report Preview --}}
        @if ($showPreview)
            <div class="lg:col-span-3">
                <div class="rounded-lg border border-slate-200 bg-white p-8 print:border-0">

                    {{-- Header --}}
                    <div class="mb-8 border-b border-slate-300 pb-6 text-center">
                        <h1 class="text-2xl font-bold text-slate-900">Sistem Pengambilan Keputusan</h1>
                        <p class="mt-1 text-slate-600">DP3AKB Kalimantan Selatan</p>
                        <p class="mt-2 text-sm text-slate-500">Laporan Analisis Risiko Perceraian</p>
                        <p class="text-xs text-slate-500">{{ $reportDate }}</p>
                    </div>

                    {{-- Executive Summary --}}
                    <div class="mb-8">
                        <h2 class="text-lg font-bold text-slate-900 mb-4">Ringkasan Eksekutif</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="rounded-lg border border-slate-200 p-4">
                                <p class="text-xs font-semibold text-slate-600">Total Kasus Perceraian</p>
                                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['totalCases'], 0, ',', '.') }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 p-4">
                                <p class="text-xs font-semibold text-slate-600">Kabupaten Berisiko Tinggi</p>
                                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $stats['highRiskCount'] }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Risk Distribution --}}
                    <div class="mb-8">
                        <h2 class="text-lg font-bold text-slate-900 mb-4">Distribusi Risiko</h2>
                        @if (count($filteredDistricts) > 0)
                            <div class="space-y-3">
                                @foreach ($riskDistribution as $rd)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-slate-700">{{ $rd['label'] }}</span>
                                        <span class="font-semibold text-slate-900">{{ $rd['count'] }} kabupaten</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="space-y-3 text-slate-400">
                                <p>Tidak ada data tersedia</p>
                            </div>
                        @endif
                    </div>

                    {{-- Detailed Data Table --}}
                    @if ($reportType === 'detailed')
                        <div class="mb-8">
                            <h2 class="text-lg font-bold text-slate-900 mb-4">Data Kabupaten</h2>
                            <div class="overflow-x-auto">
                                @if (count($filteredDistricts) > 0)
                                    <table class="w-full text-sm">
                                        <thead class="border-b border-slate-300">
                                            <tr>
                                                <th class="px-3 py-2 text-left font-semibold text-slate-900">Kabupaten</th>
                                                <th class="px-3 py-2 text-left font-semibold text-slate-900">Risiko</th>
                                                <th class="px-3 py-2 text-left font-semibold text-slate-900">Skor</th>
                                                <th class="px-3 py-2 text-left font-semibold text-slate-900">Kasus</th>
                                                <th class="px-3 py-2 text-left font-semibold text-slate-900">Populasi</th>
                                                <th class="px-3 py-2 text-left font-semibold text-slate-900">Tingkat Perceraian</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($filteredDistricts as $d)
                                                <tr class="border-b border-slate-100">
                                                    <td class="px-3 py-2 text-slate-900">{{ $d['name'] }}</td>
                                                    <td class="px-3 py-2 text-slate-700">{{ riskLabel($d['riskLevel']) }}</td>
                                                    <td class="px-3 py-2 text-slate-700">{{ $d['riskScore'] }}</td>
                                                    <td class="px-3 py-2 text-slate-700">{{ $d['divorceCount'] }}</td>
                                                    <td class="px-3 py-2 text-slate-700">{{ number_format($d['populationCount'], 0, ',', '.') }}</td>
                                                    <td class="px-3 py-2 text-slate-700">{{ $d['divorceRate'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <div class="p-4 text-center text-slate-400">Tidak ada data tersedia</div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Recommendations Summary --}}
                    <div class="mb-8">
                        <h2 class="text-lg font-bold text-slate-900 mb-4">Strategi yang Direkomendasikan</h2>
                        @if (count($recommendations) > 0)
                            <div class="space-y-4">
                                @foreach ($recommendations as $rec)
                                    <div class="rounded-lg border border-slate-200 p-4">
                                        <p class="font-semibold text-slate-900 text-sm">{{ $rec['title'] }}</p>
                                        <p class="mt-1 text-sm text-slate-600">{{ $rec['description'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="space-y-4 text-slate-400">
                                <p>Tidak ada data tersedia</p>
                            </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="border-t border-slate-300 pt-6 text-center text-xs text-slate-500">
                        <p>Sistem Pengambilan Keputusan DP3AKB Kalimantan Selatan</p>
                        <p>Laporan ini diproduksi secara otomatis dan untuk penggunaan internal</p>
                    </div>

                </div>
            </div>
        @endif
    </div>
</div>

{{-- Print Styles --}}
<style>
    @media print {
        body {
            background: white;
        }
        .print\:hidden {
            display: none !important;
        }
        .print\:border-0 {
            border: 0 !important;
        }
        aside {
            display: none !important;
        }
    }
</style>

@endsection