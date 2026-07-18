@extends('layouts.dashboard')

@section('title', 'Strategi Mediasi - KARSA')

@section('content')

<div class="space-y-6">

    <div>
        <h1 class="text-3xl font-bold text-slate-900">Strategi Mediasi</h1>
        <p class="mt-2 text-slate-600">Rekomendasi intervensi berdasarkan tingkat risiko perceraian</p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Statistik Atas --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-semibold text-slate-500">Total Program</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $totalAll }}</p>
        </div>
        <div class="rounded-lg border border-green-200 bg-green-50 p-4">
            <p class="text-xs font-semibold text-green-700">Sudah Selesai</p>
            <p class="mt-2 text-3xl font-bold text-green-700">{{ $totalDone }}</p>
        </div>
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
            <p class="text-xs font-semibold text-amber-700">Belum Dilaksanakan</p>
            <p class="mt-2 text-3xl font-bold text-amber-700">{{ $totalPending }}</p>
        </div>
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
            <p class="text-xs font-semibold text-blue-700">Persentase Selesai</p>
            <p class="mt-2 text-3xl font-bold text-blue-700">{{ $percentage }}%</p>
            <div class="mt-2 h-1.5 rounded-full bg-blue-100 overflow-hidden">
                <div class="h-full bg-blue-600 transition-all" style="width: {{ $percentage }}%"></div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('recommendation.index') }}" class="flex flex-col gap-4 lg:flex-row">
        <div class="flex-1">
            <label class="block text-sm font-medium text-slate-700 mb-1">Filter Tingkat Risiko</label>
            <select name="risk_level" onchange="this.form.submit()" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm">
                <option value="all" @selected($selectedRisk === 'all')>Semua Tingkat Risiko</option>
                <option value="critical" @selected($selectedRisk === 'critical')>Kritis</option>
                <option value="high" @selected($selectedRisk === 'high')>Tinggi</option>
                <option value="moderate" @selected($selectedRisk === 'moderate')>Sedang</option>
                <option value="low" @selected($selectedRisk === 'low')>Rendah</option>
            </select>
        </div>
        <div class="flex-1">
            <label class="block text-sm font-medium text-slate-700 mb-1">Filter Kabupaten</label>
            <select name="district" onchange="this.form.submit()" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm">
                <option value="all" @selected($selectedDist === 'all')>Semua Kabupaten</option>
                @foreach ($allDistricts as $d)
                    <option value="{{ $d->id }}" @selected($selectedDist == $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1">
            <label class="block text-sm font-medium text-slate-700 mb-1">Status Implementasi</label>
            <select name="status" onchange="this.form.submit()" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm">
                <option value="all" @selected($selectedStat === 'all')>Semua Status</option>
                <option value="pending" @selected($selectedStat === 'pending')>Belum Dilaksanakan</option>
                <option value="completed" @selected($selectedStat === 'completed')>Sudah Dilaksanakan</option>
            </select>
        </div>
    </form>

    {{-- Daftar Rekomendasi per Level Risiko --}}
    <div class="space-y-6">
        @forelse ($grouped as $level => $recs)
            @php
                $borderBg = match($level) {
                    'critical' => 'border-red-200 bg-red-50',
                    'high'     => 'border-orange-200 bg-orange-50',
                    'moderate' => 'border-yellow-200 bg-yellow-50',
                    default    => 'border-green-200 bg-green-50',
                };
                $titleColor = match($level) {
                    'critical' => 'text-red-600',
                    'high'     => 'text-orange-600',
                    'moderate' => 'text-yellow-600',
                    default    => 'text-green-600',
                };
                $levelLabel = match($level) {
                    'critical' => 'Kritis',
                    'high'     => 'Tinggi',
                    'moderate' => 'Sedang',
                    default    => 'Rendah',
                };
                $doneCount = $recs->where('is_completed', true)->count();
            @endphp

            <div class="rounded-lg border-2 p-6 {{ $borderBg }}">
                <div class="mb-4 flex items-start justify-between">
                    <div>
                        <h3 class="text-xl font-bold {{ $titleColor }}">Tingkat Risiko: {{ $levelLabel }}</h3>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ $recs->count() }} program &mdash;
                            <span class="font-medium text-green-700">{{ $doneCount }} selesai</span>,
                            <span class="font-medium text-amber-700">{{ $recs->count() - $doneCount }} belum</span>
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    @foreach ($recs as $rec)
                        @php
                            $priorityColor = match($rec->priority) {
                                'urgent' => 'bg-red-100 text-red-800',
                                'high'   => 'bg-orange-100 text-orange-800',
                                'medium' => 'bg-yellow-100 text-yellow-800',
                                default  => 'bg-green-100 text-green-800',
                            };
                            $priorityLabel = match($rec->priority) {
                                'urgent' => 'URGENT',
                                'high'   => 'TINGGI',
                                'medium' => 'SEDANG',
                                default  => 'RENDAH',
                            };
                        @endphp

                        <div class="rounded-lg bg-white p-4 border border-inherit {{ $rec->is_completed ? 'opacity-75' : '' }}">
                            <div class="flex items-start gap-3">

                                {{-- Checkbox status --}}
                                <form method="POST" action="{{ route('recommendation.toggle', $rec->id) }}" class="mt-1">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" title="{{ $rec->is_completed ? 'Batalkan' : 'Tandai Selesai' }}">
                                        @if ($rec->is_completed)
                                            <span class="flex h-5 w-5 items-center justify-center rounded bg-green-500 text-white text-xs">✓</span>
                                        @else
                                            <span class="flex h-5 w-5 items-center justify-center rounded border-2 border-slate-300 hover:border-green-500"></span>
                                        @endif
                                    </button>
                                </form>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2 flex-wrap">
                                        <div>
                                            <h4 class="font-semibold text-slate-900 {{ $rec->is_completed ? 'line-through text-slate-500' : '' }}">
                                                {{ $rec->program }}
                                            </h4>
                                            <p class="mt-1 text-sm text-slate-600 line-clamp-2">{{ $rec->description }}</p>
                                        </div>
                                        <span class="inline-block flex-shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $priorityColor }}">
                                            {{ $priorityLabel }}
                                        </span>
                                    </div>

                                    {{-- Info singkat --}}
                                    <div class="mt-3 grid grid-cols-2 gap-3 lg:grid-cols-3">
                                        <div class="rounded bg-slate-100 px-3 py-2">
                                            <p class="text-xs font-semibold text-slate-600">Lembaga</p>
                                            <p class="mt-1 text-sm font-medium text-slate-900">{{ $rec->implementing_agency }}</p>
                                        </div>
                                        <div class="rounded bg-slate-100 px-3 py-2">
                                            <p class="text-xs font-semibold text-slate-600">Target</p>
                                            <p class="mt-1 text-sm font-medium text-slate-900">{{ $rec->target_population }}</p>
                                        </div>
                                        <div class="rounded bg-slate-100 px-3 py-2">
                                            <p class="text-xs font-semibold text-slate-600">Estimasi Waktu</p>
                                            <p class="mt-1 text-sm font-medium text-slate-900">{{ $rec->estimated_duration ?? '-' }}</p>
                                        </div>
                                    </div>

                                    {{-- Status & tanggal selesai --}}
                                    @if ($rec->is_completed)
                                        <div class="mt-3 flex items-center gap-2 text-xs text-green-700">
                                            <span class="font-semibold">✓ Selesai</span>
                                            @if ($rec->completed_at)
                                                <span>— {{ \Carbon\Carbon::parse($rec->completed_at)->translatedFormat('j F Y') }}</span>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Tombol aksi --}}
                                    <div class="mt-3 flex items-center gap-2">
                                        <button
                                            type="button"
                                            onclick="showDetail({{ $rec->id }})"
                                            class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                        >
                                            Detail
                                        </button>
                                        <form method="POST" action="{{ route('recommendation.toggle', $rec->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="rounded px-3 py-1.5 text-xs font-medium {{ $rec->is_completed ? 'border border-red-300 bg-white text-red-600 hover:bg-red-50' : 'bg-green-600 text-white hover:bg-green-700' }}">
                                                {{ $rec->is_completed ? 'Batalkan' : 'Tandai Selesai' }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Modal Detail --}}
                        <div id="modal-detail-{{ $rec->id }}"
                             class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
                            <div class="w-full max-w-lg rounded-lg bg-white p-6 mx-4 relative max-h-[90vh] overflow-y-auto">
                                <button onclick="closeDetail({{ $rec->id }})"
                                        class="absolute top-3 right-4 text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>

                                <h3 class="text-lg font-bold text-slate-900 pr-6">{{ $rec->program }}</h3>
                                <span class="mt-1 inline-block rounded-full px-2.5 py-1 text-xs font-semibold {{ $priorityColor }}">
                                    {{ $priorityLabel }}
                                </span>

                                <div class="mt-4 space-y-4">
                                    <div>
                                        <p class="text-xs font-semibold text-slate-500 uppercase">Deskripsi Program</p>
                                        <p class="mt-1 text-sm text-slate-800">{{ $rec->description }}</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-xs font-semibold text-slate-500 uppercase">Prioritas</p>
                                            <p class="mt-1 text-sm font-medium text-slate-800">{{ $priorityLabel }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-slate-500 uppercase">Estimasi Waktu</p>
                                            <p class="mt-1 text-sm font-medium text-slate-800">{{ $rec->estimated_duration ?? '-' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-slate-500 uppercase">Lembaga Penyelenggara</p>
                                            <p class="mt-1 text-sm font-medium text-slate-800">{{ $rec->implementing_agency }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-slate-500 uppercase">Target Populasi</p>
                                            <p class="mt-1 text-sm font-medium text-slate-800">{{ $rec->target_population }}</p>
                                        </div>
                                    </div>
                                    @if ($rec->cause_factors && count($rec->cause_factors) > 0)
                                        <div>
                                            <p class="text-xs font-semibold text-slate-500 uppercase">Faktor Penyebab</p>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @foreach ($rec->cause_factors as $factor)
                                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">{{ $factor }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="text-xs font-semibold text-slate-500 uppercase">Status Implementasi</p>
                                        @if ($rec->is_completed)
                                            <p class="mt-1 text-sm font-medium text-green-700">
                                                ✓ Selesai
                                                @if ($rec->completed_at)
                                                    — {{ \Carbon\Carbon::parse($rec->completed_at)->translatedFormat('j F Y') }}
                                                @endif
                                            </p>
                                        @else
                                            <p class="mt-1 text-sm font-medium text-amber-700">⏳ Belum Dilaksanakan</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-6 flex gap-3">
                                    <form method="POST" action="{{ route('recommendation.toggle', $rec->id) }}" class="flex-1">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="w-full rounded px-4 py-2 text-sm font-medium {{ $rec->is_completed ? 'border border-red-300 text-red-600 hover:bg-red-50' : 'bg-green-600 text-white hover:bg-green-700' }}">
                                            {{ $rec->is_completed ? 'Batalkan Status Selesai' : 'Tandai Selesai' }}
                                        </button>
                                    </form>
                                    <button onclick="closeDetail({{ $rec->id }})"
                                        class="flex-1 rounded border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                        Tutup
                                    </button>
                                </div>
                            </div>
                        </div>

                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-slate-200 bg-white p-8 text-center text-slate-500">
                Tidak ada rekomendasi yang sesuai dengan filter.
            </div>
        @endforelse
    </div>

    {{-- Ringkasan Status Implementasi --}}
    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <h3 class="mb-4 font-semibold text-slate-900">Status Implementasi</h3>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-4">
            <div class="text-center">
                <p class="text-2xl font-bold text-slate-900">{{ $totalAll }}</p>
                <p class="text-xs text-slate-600 mt-1">Total Program</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-green-600">{{ $totalDone }}</p>
                <p class="text-xs text-slate-600 mt-1">Sudah Selesai</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-amber-600">{{ $totalPending }}</p>
                <p class="text-xs text-slate-600 mt-1">Belum Dilaksanakan</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-blue-600">{{ $percentage }}%</p>
                <p class="text-xs text-slate-600 mt-1">Persentase</p>
            </div>
        </div>
        <div class="h-3 rounded-full bg-slate-200 overflow-hidden">
            <div class="h-full bg-blue-600 transition-all duration-500" style="width: {{ $percentage }}%"></div>
        </div>
        <p class="mt-2 text-xs text-slate-500 text-right">{{ $totalDone }} dari {{ $totalAll }} program telah dilaksanakan</p>
    </div>

</div>

<script>
function showDetail(id) {
    document.getElementById('modal-detail-' + id).style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeDetail(id) {
    document.getElementById('modal-detail-' + id).style.display = 'none';
    document.body.style.overflow = '';
}
// Tutup modal jika klik di luar
document.querySelectorAll('[id^="modal-detail-"]').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
            document.body.style.overflow = '';
        }
    });
});
</script>

@endsection