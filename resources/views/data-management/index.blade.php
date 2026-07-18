@extends('layouts.dashboard')

@section('title', 'Manajemen Data - KARSA')

@section('content')

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
            <p class="font-semibold mb-1">Periksa kembali input:</p>
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
            @foreach (['cases' => 'Rekapitulasi Perceraian', 'marriage' => 'Data Pernikahan', 'population' => 'Data Populasi', 'factors' => 'Faktor Perceraian'] as $tabKey => $tabLabel)
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
            {{-- Tombol Import CSV --}}
            <a href="#modal-import"
               class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                ↑ Import CSV
            </a>
            {{-- Tombol Tambah Manual --}}
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
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Kategori</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Jenis Perkara</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-900">Jumlah</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-900">Aksi</th>
                    @elseif ($activeTab === 'marriage')
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Kabupaten/Kota</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Tahun</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-900">Jumlah Nikah</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-900">Aksi</th>
                    @elseif ($activeTab === 'population')
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Kabupaten/Kota</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Tahun</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-900">Penduduk (ribu)</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-900">Laju Tumbuh (%)</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-900">Kepadatan/km²</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-900">Aksi</th>
                    @elseif ($activeTab === 'factors')
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Kabupaten/Kota</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Tahun</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Faktor Penyebab</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-900">Jumlah Kasus</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-900">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($currentData as $item)
                    <tr class="border-t border-slate-200 hover:bg-slate-50">
                        @if ($activeTab === 'cases')
                            <td class="px-4 py-3 text-slate-900">{{ $item->district->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $item->year }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $item->kategori }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $item->jenis_perkara }}</td>
                            <td class="px-4 py-3 text-right font-medium text-slate-900">{{ number_format($item->jumlah, 0, ',', '.') }}</td>
                        @elseif ($activeTab === 'marriage')
                            <td class="px-4 py-3 text-slate-900">{{ $item->district->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $item->year }}</td>
                            <td class="px-4 py-3 text-right font-medium text-slate-900">{{ number_format($item->count, 0, ',', '.') }}</td>
                        @elseif ($activeTab === 'population')
                            <td class="px-4 py-3 text-slate-900">{{ $item->district->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $item->year }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ number_format($item->jumlah_penduduk_ribu, 1, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ $item->laju_pertumbuhan ?? '-' }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ $item->kepadatan_per_km2 ?? '-' }}</td>
                        @elseif ($activeTab === 'factors')
                            <td class="px-4 py-3 text-slate-900">{{ $item->district->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $item->year }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $item->faktor }}</td>
                            <td class="px-4 py-3 text-right font-medium text-slate-900">{{ number_format($item->jumlah_kasus, 0, ',', '.') }}</td>
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

    {{-- ===== MODAL: Import CSV ===== --}}
    <div id="modal-import" class="modal-overlay fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
        <div class="w-full max-w-lg rounded-lg bg-white p-6 mx-4 relative">
            <a href="#" class="absolute top-3 right-4 text-slate-400 hover:text-slate-600 text-xl">&times;</a>
            <h3 class="text-lg font-bold mb-1">Import CSV</h3>
            <p class="text-sm text-slate-600 mb-4">
                @if ($activeTab === 'cases')
                    Format: <code class="bg-slate-100 px-1 rounded">Pengadilan Agama | Tahun | Kategori | Jenis Perkara | Jumlah</code>
                @elseif ($activeTab === 'marriage')
                    Format: <code class="bg-slate-100 px-1 rounded">Kabupaten/Kota | Tahun | Jumlah Nikah</code>
                @elseif ($activeTab === 'population')
                    Format: <code class="bg-slate-100 px-1 rounded">Kabupaten/Kota | Tahun | Indikator | Nilai</code>
                @elseif ($activeTab === 'factors')
                    Format: <code class="bg-slate-100 px-1 rounded">Pengadilan Agama | Tahun | Faktor Penyebab | Jumlah Kasus</code>
                @endif
            </p>
            <p class="text-xs text-slate-500 mb-4">Delimiter tab atau koma otomatis terdeteksi. Baris pertama (header) akan dilewati. Data duplikat akan diperbarui.</p>

            <form method="POST"
                  action="{{ route('data-management.import.' . $activeTab) }}"
                  enctype="multipart/form-data"
                  class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Pilih file CSV</label>
                    <input type="file" name="file" accept=".csv,.txt"
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Upload & Import
                    </button>
                    <a href="#" class="flex-1 rounded border border-slate-300 px-4 py-2 text-sm text-center font-medium text-slate-700 hover:bg-slate-50">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== MODAL: Tambah Manual ===== --}}
    <div id="modal-add" class="modal-overlay fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
        <div class="w-full max-w-md rounded-lg bg-white p-6 mx-4 relative">
            <a href="#" class="absolute top-3 right-4 text-slate-400 hover:text-slate-600 text-xl">&times;</a>
            <h3 class="text-lg font-bold mb-4">Tambah Data</h3>
            <form method="POST" action="{{ route('data-management.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="tab" value="{{ $activeTab }}">

                @if ($activeTab === 'cases')
                    <select name="district_id" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Pilih Kabupaten/Kota</option>
                        @foreach ($districts as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="year" placeholder="Tahun" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    <select name="kategori" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Pilih Kategori</option>
                        <option>Diterima</option>
                        <option>Diputus</option>
                    </select>
                    <select name="jenis_perkara" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Pilih Jenis Perkara</option>
                        <option>Cerai Talak</option>
                        <option>Cerai Gugat</option>
                        <option>Jumlah</option>
                    </select>
                    <input type="number" name="jumlah" placeholder="Jumlah" required min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">

                @elseif ($activeTab === 'marriage')
                    <select name="district_id" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Pilih Kabupaten/Kota</option>
                        @foreach ($districts as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="year" placeholder="Tahun" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    <input type="number" name="count" placeholder="Jumlah Nikah" required min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">

                @elseif ($activeTab === 'population')
                    <select name="district_id" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Pilih Kabupaten/Kota</option>
                        @foreach ($districts as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="year" placeholder="Tahun" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    <input type="number" name="jumlah_penduduk_ribu" placeholder="Jumlah Penduduk (ribu, cth: 354.3)" step="0.01" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    <input type="number" name="laju_pertumbuhan" placeholder="Laju Pertumbuhan (%)" step="0.01" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    <input type="number" name="persentase_penduduk" placeholder="Persentase Penduduk (%)" step="0.01" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    <input type="number" name="kepadatan_per_km2" placeholder="Kepadatan per km²" step="0.01" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    <input type="number" name="rasio_jenis_kelamin" placeholder="Rasio Jenis Kelamin" step="0.01" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">

                @elseif ($activeTab === 'factors')
                    <select name="district_id" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Pilih Kabupaten/Kota</option>
                        @foreach ($districts as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="year" placeholder="Tahun" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    <input type="text" name="faktor" placeholder="Faktor Penyebab (cth: Ekonomi)" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    <input type="number" name="jumlah_kasus" placeholder="Jumlah Kasus" required min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                @endif

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Simpan</button>
                    <a href="#" class="flex-1 rounded border border-slate-300 px-4 py-2 text-sm text-center font-medium text-slate-700 hover:bg-slate-50">Batal</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit & Delete per item --}}
    @foreach ($currentData as $item)
        {{-- Edit --}}
        <div id="modal-edit-{{ $item->id }}" class="modal-overlay fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
            <div class="w-full max-w-md rounded-lg bg-white p-6 mx-4 relative">
                <a href="#" class="absolute top-3 right-4 text-slate-400 hover:text-slate-600 text-xl">&times;</a>
                <h3 class="text-lg font-bold mb-4">Edit Data</h3>
                <form method="POST" action="{{ route('data-management.update', ['tab' => $activeTab, 'id' => $item->id]) }}" class="space-y-4">
                    @csrf @method('PUT')

                    @if ($activeTab === 'cases')
                        <select name="district_id" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            @foreach ($districts as $d)
                                <option value="{{ $d->id }}" @selected($item->district_id == $d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="year" value="{{ $item->year }}" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        <select name="kategori" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            <option @selected($item->kategori === 'Diterima')>Diterima</option>
                            <option @selected($item->kategori === 'Diputus')>Diputus</option>
                        </select>
                        <select name="jenis_perkara" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            <option @selected($item->jenis_perkara === 'Cerai Talak')>Cerai Talak</option>
                            <option @selected($item->jenis_perkara === 'Cerai Gugat')>Cerai Gugat</option>
                            <option @selected($item->jenis_perkara === 'Jumlah')>Jumlah</option>
                        </select>
                        <input type="number" name="jumlah" value="{{ $item->jumlah }}" required min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">

                    @elseif ($activeTab === 'marriage')
                        <select name="district_id" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            @foreach ($districts as $d)
                                <option value="{{ $d->id }}" @selected($item->district_id == $d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="year" value="{{ $item->year }}" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        <input type="number" name="count" value="{{ $item->count }}" required min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">

                    @elseif ($activeTab === 'population')
                        <select name="district_id" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            @foreach ($districts as $d)
                                <option value="{{ $d->id }}" @selected($item->district_id == $d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="year" value="{{ $item->year }}" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        <input type="number" name="jumlah_penduduk_ribu" value="{{ $item->jumlah_penduduk_ribu }}" step="0.01" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        <input type="number" name="laju_pertumbuhan" value="{{ $item->laju_pertumbuhan }}" step="0.01" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Laju Pertumbuhan (%)">
                        <input type="number" name="persentase_penduduk" value="{{ $item->persentase_penduduk }}" step="0.01" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Persentase Penduduk (%)">
                        <input type="number" name="kepadatan_per_km2" value="{{ $item->kepadatan_per_km2 }}" step="0.01" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Kepadatan per km²">
                        <input type="number" name="rasio_jenis_kelamin" value="{{ $item->rasio_jenis_kelamin }}" step="0.01" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Rasio Jenis Kelamin">

                    @elseif ($activeTab === 'factors')
                        <select name="district_id" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            @foreach ($districts as $d)
                                <option value="{{ $d->id }}" @selected($item->district_id == $d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="year" value="{{ $item->year }}" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        <input type="text" name="faktor" value="{{ $item->faktor }}" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        <input type="number" name="jumlah_kasus" value="{{ $item->jumlah_kasus }}" required min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    @endif

                    <div class="flex gap-3">
                        <button type="submit" class="flex-1 rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Simpan</button>
                        <a href="#" class="flex-1 rounded border border-slate-300 px-4 py-2 text-sm text-center font-medium text-slate-700 hover:bg-slate-50">Batal</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Delete --}}
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

<style>
    .modal-overlay:target { display: flex; }
</style>

@endsection