@extends('layouts.dashboard')

@section('title', 'Manajemen Data - KARSA')

@section('content')

@php
    $hasRecaps   = Schema::hasTable('divorce_recaps');
    $hasFactRecaps = Schema::hasTable('divorce_factor_recaps');
    $popCols     = Schema::hasTable('populations') ? Schema::getColumnListing('populations') : [];
    $hasNewPop   = in_array('jumlah_penduduk_ribu', $popCols);
@endphp

<div class="space-y-6">

    <div>
        <h1 class="text-3xl font-bold text-slate-900">Manajemen Data</h1>
        <p class="mt-2 text-slate-600">Kelola semua data perceraian dan faktor risiko</p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="border-b border-slate-200">
        <div class="flex gap-6">
            @php
                $tabs = [
                    'cases'      => $hasRecaps ? 'Rekapitulasi Perceraian' : 'Kasus Perceraian',
                    'marriage'   => 'Data Pernikahan',
                    'population' => 'Data Populasi',
                    'factors'    => $hasFactRecaps ? 'Faktor Perceraian' : 'Faktor Perceraian',
                ];
            @endphp
            @foreach ($tabs as $tabKey => $tabLabel)
                <a href="{{ route('data-management.index', ['tab' => $tabKey]) }}"
                   class="border-b-2 px-4 py-3 font-medium transition-colors {{ $activeTab === $tabKey ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-600 hover:text-slate-900' }}">
                    {{ $tabLabel }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <form method="GET" action="{{ route('data-management.index') }}" class="flex-1 min-w-48">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <input type="text" name="search" value="{{ $search }}" placeholder="Cari data..."
                class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm"
                onchange="this.form.submit()">
        </form>
        <div class="flex gap-2">
            @if (Route::has('data-management.import.' . $activeTab))
                <button type="button" onclick="document.getElementById('modal-import').style.display='flex'" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">↑ Import CSV</button>
            @endif
            <a href="#modal-add"
               class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                + Tambah Manual
            </a>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50">
                <tr>
                    @if ($activeTab === 'cases')
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Kabupaten/Kota</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Tahun</th>
                        @if ($hasRecaps)
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Talak Diterima</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Gugat Diterima</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Jumlah Diterima</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Talak Diputus</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Gugat Diputus</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Jumlah Diputus</th>
                        @else
                            <th class="px-4 py-3 text-left font-semibold text-slate-900">No. Kasus</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-900">Kategori</th>
                        @endif
                        <th class="px-4 py-3 text-center font-semibold text-slate-900">Aksi</th>
                    @elseif ($activeTab === 'marriage')
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Kabupaten/Kota</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Tahun</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-900">Jumlah Nikah</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-900">Aksi</th>
                    @elseif ($activeTab === 'population')
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Kabupaten/Kota</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Tahun</th>
                        @if ($hasNewPop)
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Penduduk (ribu)</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Laju Tumbuh (%)</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">% Penduduk</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Kepadatan/km²</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Rasio JK</th>
                        @else
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Jumlah Penduduk</th>
                        @endif
                        <th class="px-4 py-3 text-center font-semibold text-slate-900">Aksi</th>
                    @elseif ($activeTab === 'factors')
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Kabupaten/Kota</th>
                        @if ($hasFactRecaps)
                            <th class="px-4 py-3 text-left font-semibold text-slate-900">Tahun</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-900">Faktor Penyebab</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Jumlah Kasus</th>
                        @else
                            <th class="px-4 py-3 text-left font-semibold text-slate-900">Faktor</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-900">Kategori</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-900">Bobot</th>
                        @endif
                        <th class="px-4 py-3 text-center font-semibold text-slate-900">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($currentData as $item)
                    <tr class="border-t border-slate-200 hover:bg-slate-50">
                        @if ($activeTab === 'cases')
                            <td class="px-4 py-3 text-slate-900">{{ $item->district_name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $item->year }}</td>
                            @if ($hasRecaps)
                                <td class="px-4 py-3 text-right text-slate-700">{{ number_format($item->cerai_talak_diterima ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ number_format($item->cerai_gugat_diterima ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-medium text-slate-900">{{ number_format($item->jumlah_diterima ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ number_format($item->cerai_talak_diputus ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ number_format($item->cerai_gugat_diputus ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-medium text-slate-900">{{ number_format($item->jumlah_diputus ?? 0, 0, ',', '.') }}</td>
                            @else
                                <td class="px-4 py-3 text-slate-700">{{ $item->case_number }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $item->category }}</td>
                            @endif
                        @elseif ($activeTab === 'marriage')
                            <td class="px-4 py-3 text-slate-900">{{ $item->district_name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $item->year }}</td>
                            <td class="px-4 py-3 text-right font-medium text-slate-900">{{ number_format($item->count, 0, ',', '.') }}</td>
                        @elseif ($activeTab === 'population')
                            <td class="px-4 py-3 text-slate-900">{{ $item->district_name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $item->year }}</td>
                            @if ($hasNewPop)
                                <td class="px-4 py-3 text-right text-slate-700">{{ number_format($item->jumlah_penduduk_ribu, 1, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ $item->laju_pertumbuhan ?? '-' }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ $item->persentase_penduduk ?? '-' }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ $item->kepadatan_per_km2 ?? '-' }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ $item->rasio_jenis_kelamin ?? '-' }}</td>
                            @else
                                <td class="px-4 py-3 text-right font-medium text-slate-900">{{ number_format($item->count, 0, ',', '.') }}</td>
                            @endif
                        @elseif ($activeTab === 'factors')
                            <td class="px-4 py-3 text-slate-900">{{ $item->district_name ?? '-' }}</td>
                            @if ($hasFactRecaps)
                                <td class="px-4 py-3 text-slate-700">{{ $item->year }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $item->faktor }}</td>
                                <td class="px-4 py-3 text-right font-medium text-slate-900">{{ number_format($item->jumlah_kasus, 0, ',', '.') }}</td>
                            @else
                                <td class="px-4 py-3 text-slate-700">{{ $item->name }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $item->category }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ $item->weight }}</td>
                            @endif
                        @endif
                        <td class="px-4 py-3 text-center">
                            <a href="#modal-edit-{{ $item->id }}" class="mr-2 text-blue-600 hover:text-blue-700 text-sm">Edit</a>
                            <a href="#modal-delete-{{ $item->id }}" class="text-red-600 hover:text-red-700 text-sm">Hapus</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                            Belum ada data. Gunakan tombol <strong>Import CSV</strong> atau <strong>Tambah Manual</strong>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($currentData->hasPages())
        <div class="flex items-center justify-between">
            <p class="text-sm text-slate-600">Menampilkan {{ $currentData->count() }} dari {{ $currentData->total() }} data</p>
            {{ $currentData->onEachSide(1)->links('pagination::simple-tailwind') }}
        </div>
    @endif

    {{-- Modal Import CSV --}}
    @if (Route::has('data-management.import.' . $activeTab))
    <div id="modal-import" class="fixed inset-0 z-50 items-center justify-center bg-black/50" style="display:none">
        <div class="w-full max-w-lg rounded-lg bg-white p-6 mx-4 relative">
            <button type="button" onclick="document.getElementById('modal-import').style.display='none'" class="absolute top-3 right-4 text-slate-400 hover:text-slate-600 text-xl">&times;</button>
            <h3 class="text-lg font-bold mb-2">Import CSV</h3>
            <div class="mb-4 rounded bg-slate-50 border border-slate-200 p-3 text-xs text-slate-600">
                <p class="font-semibold mb-1">Format kolom (baris pertama = header, dilewati otomatis):</p>
                @if ($activeTab === 'cases')
                    <code>Pengadilan Agama | Tahun | Kategori | Jenis Perkara | Jumlah</code><br>
                    <p class="mt-1">Kategori: <em>Diterima</em> atau <em>Diputus</em></p>
                    <p>Jenis Perkara: <em>Cerai Talak</em>, <em>Cerai Gugat</em>, atau <em>Jumlah</em></p>
                @elseif ($activeTab === 'marriage')
                    <code>Kabupaten/Kota | Tahun | Jumlah Nikah</code>
                @elseif ($activeTab === 'population')
                    <code>Kabupaten/Kota | Tahun | Indikator | Nilai</code><br>
                    <p class="mt-1">Indikator: <em>Jumlah Penduduk (Ribu)</em>, <em>Laju Pertumbuhan Penduduk per Tahun</em>, <em>Persentase Penduduk</em>, <em>Kepadatan Penduduk per km persegi (Km2)</em>, <em>Rasio Jenis Kelamin Penduduk</em></p>
                @elseif ($activeTab === 'factors')
                    <code>Pengadilan Agama | Tahun | Faktor Penyebab | Jumlah Kasus</code>
                @endif
                <p class="mt-2 text-slate-500">Delimiter tab atau koma otomatis terdeteksi. Data duplikat akan diperbarui.</p>
            </div>
            <form method="POST" action="{{ route('data-management.import.' . $activeTab) }}"
                  enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="file" name="file" accept=".csv,.txt,.xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv"
                    class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                <p class="text-xs text-slate-400 mt-1">Format: <strong>.xlsx</strong>, <strong>.xls</strong>, <strong>.csv</strong></p>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Upload & Import
                    </button>
                    <button type="button" onclick="document.getElementById('modal-import').style.display='none'" class="flex-1 rounded border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Modal Tambah Manual --}}
    <div id="modal-add" class="modal-overlay fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
        <div class="w-full max-w-md rounded-lg bg-white p-6 mx-4 relative max-h-[90vh] overflow-y-auto">
            <a href="#" class="absolute top-3 right-4 text-slate-400 hover:text-slate-600 text-xl">&times;</a>
            <h3 class="text-lg font-bold mb-4">Tambah Data</h3>
            <form method="POST" action="{{ route('data-management.store') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="tab" value="{{ $activeTab }}">

                @if ($activeTab === 'cases')
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Kabupaten/Kota</label>
                        <select name="district_id" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Pilih Kabupaten/Kota</option>
                            @foreach ($districts as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Tahun</label>
                        <input type="number" name="year" placeholder="cth: 2024" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    @if ($hasRecaps)
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Cerai Talak Diterima</label>
                            <input type="number" name="cerai_talak_diterima" min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Cerai Gugat Diterima</label>
                            <input type="number" name="cerai_gugat_diterima" min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Jumlah Diterima</label>
                            <input type="number" name="jumlah_diterima" min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Cerai Talak Diputus</label>
                            <input type="number" name="cerai_talak_diputus" min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Cerai Gugat Diputus</label>
                            <input type="number" name="cerai_gugat_diputus" min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Jumlah Diputus</label>
                            <input type="number" name="jumlah_diputus" min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    @else
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">No. Kasus</label>
                            <input type="text" name="case_number" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Kategori</label>
                            <input type="text" name="category" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    @endif

                @elseif ($activeTab === 'marriage')
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Kabupaten/Kota</label>
                        <select name="district_id" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Pilih Kabupaten/Kota</option>
                            @foreach ($districts as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Tahun</label>
                        <input type="number" name="year" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Jumlah Nikah</label>
                        <input type="number" name="count" required min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>

                @elseif ($activeTab === 'population')
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Kabupaten/Kota</label>
                        <select name="district_id" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Pilih Kabupaten/Kota</option>
                            @foreach ($districts as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Tahun</label>
                        <input type="number" name="year" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    @if ($hasNewPop)
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Jumlah Penduduk (ribu)</label>
                            <input type="text" name="jumlah_penduduk_ribu" placeholder="cth: 354.3" inputmode="decimal" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Laju Pertumbuhan (%)</label>
                            <input type="text" name="laju_pertumbuhan" inputmode="decimal" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Persentase Penduduk (%)</label>
                            <input type="text" name="persentase_penduduk" inputmode="decimal" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Kepadatan per km²</label>
                            <input type="text" name="kepadatan_per_km2" inputmode="decimal" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Rasio Jenis Kelamin</label>
                            <input type="text" name="rasio_jenis_kelamin" inputmode="decimal" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    @else
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Jumlah Penduduk</label>
                            <input type="number" name="count" required min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    @endif

                @elseif ($activeTab === 'factors')
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Kabupaten/Kota</label>
                        <select name="district_id" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Pilih Kabupaten/Kota</option>
                            @foreach ($districts as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($hasFactRecaps)
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Tahun</label>
                            <input type="number" name="year" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Faktor Penyebab</label>
                            <select name="faktor" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Pilih Faktor Penyebab</option>
                            <option>Zina</option>
                            <option>Mabuk</option>
                            <option>Madat</option>
                            <option>Judi</option>
                            <option>Meninggalkan Salah Satu Pihak</option>
                            <option>Dihukum Penjara</option>
                            <option>Poligami</option>
                            <option>KDRT</option>
                            <option>Cacat Badan</option>
                            <option>Perselisihan dan Pertengkaran Terus Menerus</option>
                            <option>Kawin Paksa</option>
                            <option>Murtad</option>
                            <option>Ekonomi</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Jumlah Kasus</label>
                            <input type="number" name="jumlah_kasus" required min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    @else
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Nama Faktor</label>
                            <input type="text" name="name" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Kategori</label>
                            <input type="text" name="category" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Bobot (0-100)</label>
                            <input type="number" name="weight" min="0" max="100" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    @endif
                @endif

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Simpan</button>
                    <a href="#" class="flex-1 rounded border border-slate-300 px-4 py-2 text-sm text-center font-medium text-slate-700 hover:bg-slate-50">Batal</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit & Delete --}}
    @foreach ($currentData as $item)
        <div id="modal-edit-{{ $item->id }}" class="modal-overlay fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
            <div class="w-full max-w-md rounded-lg bg-white p-6 mx-4 relative max-h-[90vh] overflow-y-auto">
                <a href="#" class="absolute top-3 right-4 text-slate-400 hover:text-slate-600 text-xl">&times;</a>
                <h3 class="text-lg font-bold mb-4">Edit Data</h3>
                <form method="POST" action="{{ route('data-management.update', ['tab' => $activeTab, 'id' => $item->id]) }}" class="space-y-3">
                    @csrf @method('PUT')

                    @if ($activeTab === 'cases')
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Kabupaten/Kota</label>
                            @if ($hasRecaps)
                                <select disabled onmousedown="event.preventDefault(); alert('Kabupaten/Kota tidak dapat diubah melalui form Edit, karena satu baris data mewakili kombinasi Kabupaten + Tahun yang unik. Untuk memindahkan data ke kabupaten lain, hapus data ini lalu tambahkan data baru lewat tombol \'Tambah Manual\'.');" class="w-full rounded border border-slate-300 bg-slate-100 text-slate-500 px-3 py-2 text-sm cursor-not-allowed">
                                    @foreach ($districts as $d)
                                        <option value="{{ $d->id }}" @selected($item->district_id == $d->id)>{{ $d->name }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="district_id" value="{{ $item->district_id }}">
                                <p class="text-xs text-slate-400 mt-1">🔒 Tidak dapat diubah saat edit</p>
                            @else
                                <select name="district_id" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                                    @foreach ($districts as $d)
                                        <option value="{{ $d->id }}" @selected($item->district_id == $d->id)>{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Tahun</label>
                            @if ($hasRecaps)
                                <input type="number" name="year" value="{{ $item->year }}" readonly onclick="alert('Tahun tidak dapat diubah melalui form Edit, karena satu baris data mewakili kombinasi Kabupaten + Tahun yang unik. Untuk memindahkan data ke tahun lain, hapus data ini lalu tambahkan data baru lewat tombol \'Tambah Manual\'.');" class="w-full rounded border border-slate-300 bg-slate-100 text-slate-500 px-3 py-2 text-sm cursor-not-allowed">
                                <p class="text-xs text-slate-400 mt-1">🔒 Tidak dapat diubah saat edit</p>
                            @else
                                <input type="number" name="year" value="{{ $item->year }}" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            @endif
                        </div>
                        @if ($hasRecaps)
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Cerai Talak Diterima</label>
                                <input type="number" name="cerai_talak_diterima" value="{{ $item->cerai_talak_diterima }}" min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Cerai Gugat Diterima</label>
                                <input type="number" name="cerai_gugat_diterima" value="{{ $item->cerai_gugat_diterima }}" min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Jumlah Diterima</label>
                                <input type="number" name="jumlah_diterima" value="{{ $item->jumlah_diterima }}" min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Cerai Talak Diputus</label>
                                <input type="number" name="cerai_talak_diputus" value="{{ $item->cerai_talak_diputus }}" min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Cerai Gugat Diputus</label>
                                <input type="number" name="cerai_gugat_diputus" value="{{ $item->cerai_gugat_diputus }}" min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Jumlah Diputus</label>
                                <input type="number" name="jumlah_diputus" value="{{ $item->jumlah_diputus }}" min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            </div>
                        @else
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">No. Kasus</label>
                                <input type="text" name="case_number" value="{{ $item->case_number }}" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Kategori</label>
                                <input type="text" name="category" value="{{ $item->category }}" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            </div>
                        @endif

                    @elseif ($activeTab === 'marriage')
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Kabupaten/Kota</label>
                            <select disabled onmousedown="event.preventDefault(); alert('Kabupaten/Kota tidak dapat diubah melalui form Edit, karena satu baris data mewakili kombinasi Kabupaten + Tahun yang unik. Untuk memindahkan data ke kabupaten lain, hapus data ini lalu tambahkan data baru lewat tombol \'Tambah Manual\'.');" class="w-full rounded border border-slate-300 bg-slate-100 text-slate-500 px-3 py-2 text-sm cursor-not-allowed">
                                @foreach ($districts as $d)
                                    <option value="{{ $d->id }}" @selected($item->district_id == $d->id)>{{ $d->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="district_id" value="{{ $item->district_id }}">
                            <p class="text-xs text-slate-400 mt-1">🔒 Tidak dapat diubah saat edit</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Tahun</label>
                            <input type="number" name="year" value="{{ $item->year }}" readonly onclick="alert('Tahun tidak dapat diubah melalui form Edit, karena satu baris data mewakili kombinasi Kabupaten + Tahun yang unik. Untuk memindahkan data ke tahun lain, hapus data ini lalu tambahkan data baru lewat tombol \'Tambah Manual\'.');" class="w-full rounded border border-slate-300 bg-slate-100 text-slate-500 px-3 py-2 text-sm cursor-not-allowed">
                            <p class="text-xs text-slate-400 mt-1">🔒 Tidak dapat diubah saat edit</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Jumlah Nikah</label>
                            <input type="number" name="count" value="{{ $item->count }}" required min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>

                    @elseif ($activeTab === 'population')
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Kabupaten/Kota</label>
                            <select disabled onmousedown="event.preventDefault(); alert('Kabupaten/Kota tidak dapat diubah melalui form Edit, karena satu baris data mewakili kombinasi Kabupaten + Tahun yang unik. Untuk memindahkan data ke kabupaten lain, hapus data ini lalu tambahkan data baru lewat tombol \'Tambah Manual\'.');" class="w-full rounded border border-slate-300 bg-slate-100 text-slate-500 px-3 py-2 text-sm cursor-not-allowed">
                                @foreach ($districts as $d)
                                    <option value="{{ $d->id }}" @selected($item->district_id == $d->id)>{{ $d->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="district_id" value="{{ $item->district_id }}">
                            <p class="text-xs text-slate-400 mt-1">🔒 Tidak dapat diubah saat edit</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Tahun</label>
                            <input type="number" name="year" value="{{ $item->year }}" readonly onclick="alert('Tahun tidak dapat diubah melalui form Edit, karena satu baris data mewakili kombinasi Kabupaten + Tahun yang unik. Untuk memindahkan data ke tahun lain, hapus data ini lalu tambahkan data baru lewat tombol \'Tambah Manual\'.');" class="w-full rounded border border-slate-300 bg-slate-100 text-slate-500 px-3 py-2 text-sm cursor-not-allowed">
                            <p class="text-xs text-slate-400 mt-1">🔒 Tidak dapat diubah saat edit</p>
                        </div>
                        @if ($hasNewPop)
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Jumlah Penduduk (ribu)</label>
                                <input type="text" name="jumlah_penduduk_ribu" value="{{ $item->jumlah_penduduk_ribu }}" inputmode="decimal" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Laju Pertumbuhan (%)</label>
                                <input type="text" name="laju_pertumbuhan" value="{{ $item->laju_pertumbuhan }}" inputmode="decimal" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Persentase Penduduk (%)</label>
                                <input type="text" name="persentase_penduduk" value="{{ $item->persentase_penduduk }}" inputmode="decimal" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Kepadatan per km²</label>
                                <input type="text" name="kepadatan_per_km2" value="{{ $item->kepadatan_per_km2 }}" inputmode="decimal" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Rasio Jenis Kelamin</label>
                                <input type="text" name="rasio_jenis_kelamin" value="{{ $item->rasio_jenis_kelamin }}" inputmode="decimal" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            </div>
                        @else
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Jumlah Penduduk</label>
                                <input type="number" name="count" value="{{ $item->count }}" required min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            </div>
                        @endif

                    @elseif ($activeTab === 'factors')
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Kabupaten/Kota</label>
                            @if ($hasFactRecaps)
                                <select disabled onmousedown="event.preventDefault(); alert('Kabupaten/Kota tidak dapat diubah melalui form Edit, karena satu baris data mewakili kombinasi Kabupaten + Tahun + Faktor yang unik. Untuk memindahkan data ke kabupaten lain, hapus data ini lalu tambahkan data baru lewat tombol \'Tambah Manual\'.');" class="w-full rounded border border-slate-300 bg-slate-100 text-slate-500 px-3 py-2 text-sm cursor-not-allowed">
                                    @foreach ($districts as $d)
                                        <option value="{{ $d->id }}" @selected($item->district_id == $d->id)>{{ $d->name }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="district_id" value="{{ $item->district_id }}">
                                <p class="text-xs text-slate-400 mt-1">🔒 Tidak dapat diubah saat edit</p>
                            @else
                                <select name="district_id" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                                    @foreach ($districts as $d)
                                        <option value="{{ $d->id }}" @selected($item->district_id == $d->id)>{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        @if ($hasFactRecaps)
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Tahun</label>
                                <input type="number" name="year" value="{{ $item->year }}" readonly onclick="alert('Tahun tidak dapat diubah melalui form Edit, karena satu baris data mewakili kombinasi Kabupaten + Tahun + Faktor yang unik. Untuk memindahkan data ke tahun lain, hapus data ini lalu tambahkan data baru lewat tombol \'Tambah Manual\'.');" class="w-full rounded border border-slate-300 bg-slate-100 text-slate-500 px-3 py-2 text-sm cursor-not-allowed">
                                <p class="text-xs text-slate-400 mt-1">🔒 Tidak dapat diubah saat edit</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Faktor Penyebab</label>
                                <select disabled onmousedown="event.preventDefault(); alert('Faktor Penyebab tidak dapat diubah melalui form Edit, karena satu baris data mewakili kombinasi Kabupaten + Tahun + Faktor yang unik. Untuk mengganti faktor, hapus data ini lalu tambahkan data baru lewat tombol \'Tambah Manual\'.');" class="w-full rounded border border-slate-300 bg-slate-100 text-slate-500 px-3 py-2 text-sm cursor-not-allowed">
                                    <option value="">Pilih Faktor Penyebab</option>
                                    @foreach(['Zina','Mabuk','Madat','Judi','Meninggalkan Salah Satu Pihak','Dihukum Penjara','Poligami','KDRT','Cacat Badan','Perselisihan dan Pertengkaran Terus Menerus','Kawin Paksa','Murtad','Ekonomi'] as $f)
                                        <option @selected($item->faktor === $f)>{{ $f }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="faktor" value="{{ $item->faktor }}">
                                <p class="text-xs text-slate-400 mt-1">🔒 Tidak dapat diubah saat edit</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Jumlah Kasus</label>
                                <input type="number" name="jumlah_kasus" value="{{ $item->jumlah_kasus }}" required min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            </div>
                        @else
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Nama Faktor</label>
                                <input type="text" name="name" value="{{ $item->name }}" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Kategori</label>
                                <input type="text" name="category" value="{{ $item->category }}" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Bobot (0-100)</label>
                                <input type="number" name="weight" value="{{ $item->weight }}" min="0" max="100" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            </div>
                        @endif
                    @endif

                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="flex-1 rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Simpan</button>
                        <a href="#" class="flex-1 rounded border border-slate-300 px-4 py-2 text-sm text-center font-medium text-slate-700 hover:bg-slate-50">Batal</a>
                    </div>
                </form>
            </div>
        </div>

        <div id="modal-delete-{{ $item->id }}" class="modal-overlay fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
            <div class="w-full max-w-sm rounded-lg bg-white p-6 mx-4 relative">
                <a href="#" class="absolute top-3 right-4 text-slate-400 hover:text-slate-600 text-xl">&times;</a>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Hapus Data?</h3>
                <p class="text-slate-600 mb-6 text-sm">Tindakan ini tidak dapat dibatalkan.</p>
                <form method="POST" action="{{ route('data-management.destroy', ['tab' => $activeTab, 'id' => $item->id]) }}">
                    @csrf @method('DELETE')
                    <div class="flex gap-3">
                        <button type="submit" class="flex-1 rounded bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">Hapus</button>
                        <a href="#" class="flex-1 rounded border border-slate-300 px-4 py-2 text-sm text-center font-medium text-slate-700 hover:bg-slate-50">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

</div>

<style>.modal-overlay:target { display: flex; }</style>

@endsection