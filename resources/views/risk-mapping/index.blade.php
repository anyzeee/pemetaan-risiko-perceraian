@extends('layouts.dashboard')

@section('title', 'Pemetaan Risiko - KARSA')

@section('content')

<div class="space-y-6">

    <div>
        <h1 class="text-3xl font-bold text-slate-900">Pemetaan Risiko</h1>
        <p class="mt-2 text-slate-600">Visualisasi risiko perceraian berdasarkan wilayah kabupaten</p>
    </div>

    {{-- Risk Statistics --}}
    <div class="grid gap-6 lg:grid-cols-4">
        @foreach ($riskStats as $stat)
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-sm font-medium text-slate-600">{{ $stat['level'] }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stat['count'] }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $stat['percentage'] }}% dari total</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Map --}}
        <div class="lg:col-span-2">
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900">Peta Kabupaten/Kota Kalimantan Selatan</h3>
                    {{-- Legend --}}
                    <div class="flex items-center gap-3 text-xs">
                        <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full bg-red-500"></span> Kritis</span>
                        <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full bg-orange-500"></span> Tinggi</span>
                        <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full bg-yellow-500"></span> Sedang</span>
                        <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full bg-green-500"></span> Rendah</span>
                    </div>
                </div>
                {{-- Leaflet Map Container --}}
                <div id="map" class="h-[480px] w-full rounded-lg z-0"></div>
                <p class="mt-2 text-xs text-slate-500">Klik marker pada peta untuk melihat detail risiko kabupaten</p>
            </div>
        </div>

        {{-- Detail Sidebar --}}
        <div class="space-y-4">

            {{-- Detail panel kosong (default) --}}
            <div id="detail-empty" class="rounded-lg border border-slate-200 bg-slate-50 p-6 text-center">
                <p class="text-sm text-slate-600">Klik marker pada peta untuk melihat detail risiko</p>
            </div>

            {{-- Detail panel (diisi via JS) --}}
            <div id="detail-panel" class="hidden rounded-lg border p-6">
                <div class="mb-4">
                    <h3 id="detail-name" class="text-lg font-bold text-slate-900"></h3>
                    <p id="detail-badge" class="mt-1 inline-block rounded-full px-3 py-1 text-xs font-semibold"></p>
                </div>
                <div class="space-y-4 border-t pt-4">
                    <div>
                        <p class="text-xs font-semibold text-slate-600">SKOR RISIKO</p>
                        <div class="mt-2 flex items-end gap-2">
                            <p id="detail-score" class="text-2xl font-bold text-slate-900"></p>
                            <p class="text-xs text-slate-600">/100</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-600">TINGKAT PERCERAIAN</p>
                        <p id="detail-rate" class="mt-2 text-lg font-bold text-slate-900"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded bg-white/60 border p-3">
                            <p class="text-xs text-slate-600">Kasus Perceraian</p>
                            <p id="detail-count" class="mt-1 font-bold text-slate-900"></p>
                        </div>
                        <div class="rounded bg-white/60 border p-3">
                            <p class="text-xs text-slate-600">Populasi</p>
                            <p id="detail-population" class="mt-1 font-bold text-slate-900"></p>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-600">FAKTOR UTAMA</p>
                        <div id="detail-factors" class="mt-2 space-y-2"></div>
                    </div>
                    <div class="border-t pt-4">
                        <p class="text-xs font-semibold text-slate-600 mb-2">INTERVENSI YANG DIREKOMENDASIKAN</p>
                        <p id="detail-recommendation" class="text-sm text-slate-900"></p>
                    </div>
                </div>
            </div>

            {{-- Daftar Kabupaten --}}
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h4 class="mb-3 font-semibold text-slate-900">Daftar Kabupaten</h4>
                <div class="space-y-2 max-h-72 overflow-y-auto">
                    @foreach ($districts as $d)
                        <button
                            type="button"
                            onclick="selectDistrict({{ $d['id'] }})"
                            data-district-id="{{ $d['id'] }}"
                            class="list-btn w-full text-left rounded-lg px-3 py-2 text-sm transition-colors hover:bg-slate-100"
                        >
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-slate-700">{{ $d['name'] }}</span>
                                <span class="inline-block rounded-full px-2 py-1 text-xs font-semibold {{ riskBadgeClasses($d['riskLevel']) }}">
                                    {{ riskLabel($d['riskLevel']) }}
                                </span>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Leaflet CSS & JS --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// Data kabupaten dari PHP ke JS
const districtsData = {!! json_encode(array_values($districts)) !!};

// Koordinat pusat tiap kabupaten/kota Kalsel
const districtCoords = {
    'Kabupaten Banjar':              [-3.4607, 115.2193],
    'Kabupaten Barito Kuala':        [-3.0714, 114.7523],
    'Kabupaten Balangan':            [-2.1000, 115.3000],
    'Kabupaten Hulu Sungai Selatan': [-2.5197, 115.4029],
    'Kabupaten Hulu Sungai Tengah':  [-2.3167, 115.3667],
    'Kabupaten Hulu Sungai Utara':   [-2.0667, 115.2333],
    'Kabupaten Kotabaru':            [-3.2833, 116.2167],
    'Kabupaten Tabalong':            [-2.0500, 115.4833],
    'Kabupaten Tanah Bumbu':         [-3.4833, 115.8167],
    'Kabupaten Tanah Laut':          [-3.7167, 115.0167],
    'Kabupaten Tapin':               [-3.0167, 115.0000],
    'Kota Banjarbaru':               [-3.4422, 114.8334],
    'Kabupaten Banjarmasin':         [-3.3167, 114.5920],
};

const riskColors = {
    critical: '#ef4444',
    high:     '#f97316',
    moderate: '#eab308',
    low:      '#22c55e',
};

const riskLabels = {
    critical: 'Kritis',
    high:     'Tinggi',
    moderate: 'Sedang',
    low:      'Rendah',
};

const riskBadgeClasses = {
    critical: 'background:#fee2e2;color:#991b1b',
    high:     'background:#fef3c7;color:#92400e',
    moderate: 'background:#fef9c3;color:#713f12',
    low:      'background:#dcfce7;color:#166534',
};

const recommendationMap = {
    critical: 'Mediasi intensif, konseling PUSPAGA, dan pencegahan KDRT diprioritaskan.',
    high:     'Mediasi diperkuat dan program kesadaran komunitas direkomendasikan.',
    moderate: 'Monitoring berkala dan program pencegahan untuk masyarakat umum.',
    low:      'Pendidikan preventif dan keterlibatan komunitas berkelanjutan.',
};

// Inisialisasi peta Leaflet
const map = L.map('map').setView([-2.8, 115.5], 7);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 13,
}).addTo(map);

// Tambahkan marker per kabupaten
const markers = {};

districtsData.forEach(district => {
    const coords = districtCoords[district.name];
    if (!coords) return;

    const color  = riskColors[district.riskLevel] || '#94a3b8';
    const radius = district.riskLevel === 'critical' ? 18 : district.riskLevel === 'high' ? 15 : 12;

    const marker = L.circleMarker(coords, {
        radius:      radius,
        fillColor:   color,
        color:       '#fff',
        weight:      2,
        opacity:     1,
        fillOpacity: 0.85,
    }).addTo(map);

    // Tooltip nama kabupaten saat hover
    marker.bindTooltip(`<strong>${district.name}</strong><br>Risiko: ${riskLabels[district.riskLevel]} (${district.riskScore})`, {
        direction: 'top',
        offset: [0, -10],
    });

    // Klik marker → update sidebar detail
    marker.on('click', () => selectDistrict(district.id));

    markers[district.id] = { marker, district };
});

let selectedId = null;

function selectDistrict(id) {
    selectedId = id;
    const entry = markers[id];
    if (!entry) return;

    const district = entry.district;

    // Reset semua marker ke normal
    Object.values(markers).forEach(({ marker, district: d }) => {
        marker.setStyle({
            weight: 2,
            color: '#fff',
            fillOpacity: 0.85,
        });
    });

    // Highlight marker yang dipilih
    entry.marker.setStyle({ weight: 4, color: '#1e40af', fillOpacity: 1 });
    map.panTo(markers[id].marker.getLatLng());

    // Highlight list
    document.querySelectorAll('.list-btn').forEach(btn => {
        const btnId = parseInt(btn.getAttribute('data-district-id'));
        btn.className = `list-btn w-full text-left rounded-lg px-3 py-2 text-sm transition-colors ${
            btnId === id ? 'bg-blue-100 text-blue-900' : 'hover:bg-slate-100'
        }`;
    });

    // Update panel detail
    document.getElementById('detail-empty').classList.add('hidden');
    const panel = document.getElementById('detail-panel');
    panel.classList.remove('hidden');

    const bgClass = {
        critical: 'bg-red-50 border-red-200',
        high:     'bg-orange-50 border-orange-200',
        moderate: 'bg-yellow-50 border-yellow-200',
        low:      'bg-green-50 border-green-200',
    }[district.riskLevel] || 'bg-slate-50 border-slate-200';

    panel.className = `rounded-lg border p-6 ${bgClass}`;

    document.getElementById('detail-name').textContent  = district.name;

    const badge = document.getElementById('detail-badge');
    badge.textContent = riskLabels[district.riskLevel] || '-';
    badge.setAttribute('style', riskBadgeClasses[district.riskLevel] || '');

    document.getElementById('detail-score').textContent      = district.riskScore;
    document.getElementById('detail-rate').textContent       = Number(district.divorceRate).toFixed(1) + ' per 1.000';
    document.getElementById('detail-count').textContent      = district.divorceCount.toLocaleString('id-ID');
    document.getElementById('detail-population').textContent = district.populationCount > 0
        ? Math.round(district.populationCount / 1000) + 'K'
        : '-';

    const factorsEl = document.getElementById('detail-factors');
    factorsEl.innerHTML = '';
    (district.mainFactors || []).forEach((factor, idx) => {
        const row = document.createElement('div');
        row.className = 'flex items-center gap-2 rounded bg-white/50 px-3 py-2';
        row.innerHTML = `
            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white/70 text-xs font-bold text-slate-900">${idx + 1}</span>
            <span class="text-sm font-medium text-slate-900">${factor}</span>
        `;
        factorsEl.appendChild(row);
    });

    document.getElementById('detail-recommendation').textContent = recommendationMap[district.riskLevel] || '';
}
</script>

@endsection