@extends('layouts.dashboard')

@section('title', 'Analisis Clustering - KARSA')

@section('content')

<div class="space-y-6">

    <div>
        <h1 class="text-3xl font-bold text-slate-900">Analisis Clustering K-Means</h1>
        <p class="mt-2 text-slate-600">Pengelompokan kabupaten berdasarkan tingkat risiko perceraian</p>
    </div>

    {{-- Cluster Overview --}}
    <div class="grid gap-4 lg:grid-cols-3">
        @foreach ($clusters as $cluster)
            <div class="rounded-lg border p-4 {{ $cluster['color']['bg'] }} {{ $cluster['color']['border'] }}">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $cluster['title'] }}</h3>
                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ count($cluster['data']['districts']) }}</p>
                        <p class="text-xs text-slate-600">kabupaten</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $cluster['color']['icon'] }}">
                        <span class="text-lg font-bold">{{ $cluster['data']['averageRiskScore'] }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Detailed Cluster Analysis --}}
    <div class="space-y-4">
        @foreach ($clusters as $cluster)
            @php
                $isOpen = $expandedCluster === $cluster['id'];
                $nextExpand = $isOpen ? '' : $cluster['id'];
            @endphp
            <div class="rounded-lg border {{ $cluster['color']['border'] }} {{ $cluster['color']['bg'] }} overflow-hidden">

                <a href="{{ route('clustering-analysis.index', ['expand' => $nextExpand]) }}"
                   class="w-full px-6 py-4 flex items-center justify-between hover:opacity-80 transition-opacity block">
                    <div class="text-left">
                        <h3 class="font-semibold text-slate-900">{{ $cluster['title'] }}</h3>
                        <p class="text-sm text-slate-600">Skor risiko rata-rata: {{ $cluster['data']['averageRiskScore'] }}</p>
                    </div>
                    <svg class="h-5 w-5 transition-transform {{ $isOpen ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                </a>

                @if ($isOpen)
                    <div class="border-t px-6 py-4 space-y-4">

                        {{-- Characteristics --}}
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-2">Karakteristik Cluster</h4>
                            <ul class="space-y-1">
                                @foreach ($cluster['data']['characteristics'] as $char)
                                    <li class="flex items-start gap-2 text-sm text-slate-700">
                                        <span class="mt-1 h-1.5 w-1.5 rounded-full bg-slate-400 flex-shrink-0"></span>
                                        {{ $char }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- Main Factors --}}
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-2">Faktor Utama</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($cluster['data']['mainFactors'] as $factor)
                                    <span class="inline-block rounded-full bg-white/50 px-3 py-1 text-xs font-medium text-slate-900">{{ $factor }}</span>
                                @endforeach
                            </div>
                        </div>

                        {{-- Districts List --}}
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-2">
                                Kabupaten dalam Cluster ({{ count($cluster['data']['districts']) }})
                            </h4>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($cluster['data']['districts'] as $district)
                                    <div class="rounded-lg bg-white/50 px-3 py-2 text-sm">
                                        <div class="font-medium text-slate-900">{{ $district['name'] }}</div>
                                        <div class="text-xs text-slate-600">Risiko: {{ $district['riskScore'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Suggested Actions --}}
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-2">Tindakan yang Disarankan</h4>
                            <div class="space-y-2">
                                @foreach ($actionsByCluster[$cluster['id']] as $action)
                                    <div class="rounded-lg bg-white/50 px-3 py-2">
                                        <p class="font-medium text-slate-900 text-sm">{{ $action['title'] }}</p>
                                        <p class="text-xs text-slate-600 mt-1">Prioritas: {{ $action['priority'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                    </div>
                @endif

            </div>
        @endforeach
    </div>

    {{-- Cluster Comparison --}}
    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <h3 class="mb-4 text-lg font-semibold text-slate-900">Perbandingan Cluster</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Metrik</th>
                        @foreach ($clusters as $cluster)
                            <th class="px-4 py-3 text-left font-semibold text-slate-900">{{ $cluster['title'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-slate-200">
                        <td class="px-4 py-3 font-medium text-slate-900">Jumlah Kabupaten</td>
                        @foreach ($clusters as $cluster)
                            <td class="px-4 py-3 text-slate-700">{{ count($cluster['data']['districts']) }}</td>
                        @endforeach
                    </tr>
                    <tr class="border-b border-slate-200">
                        <td class="px-4 py-3 font-medium text-slate-900">Skor Risiko Rata-rata</td>
                        @foreach ($clusters as $cluster)
                            <td class="px-4 py-3 text-slate-700">{{ $cluster['data']['averageRiskScore'] }}</td>
                        @endforeach
                    </tr>
                    <tr class="border-b border-slate-200">
                        <td class="px-4 py-3 font-medium text-slate-900">Prioritas Intervensi</td>
                        @foreach ($clusters as $cluster)
                            <td class="px-4 py-3 text-slate-700">{{ $cluster['priority'] }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">Total Kasus Perceraian</td>
                        @foreach ($clusters as $cluster)
                            @php $total = array_sum(array_column($cluster['data']['districts'], 'divorceCount')); @endphp
                            <td class="px-4 py-3 text-slate-700">{{ number_format($total, 0, ',', '.') }}</td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection