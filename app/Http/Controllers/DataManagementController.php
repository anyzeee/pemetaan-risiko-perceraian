<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\DivorceFactorRecap;
use App\Models\DivorceRecap;
use App\Models\Marriage;
use App\Models\Population;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DataManagementController extends Controller
{
    private const TABS = ['cases', 'marriage', 'population', 'factors'];

    public function index(Request $request): View
    {
        $activeTab = $request->query('tab', 'cases');
        if (!in_array($activeTab, self::TABS, true)) {
            $activeTab = 'cases';
        }

        $search   = (string) $request->query('search', '');
        $pageSize = 10;

        $districts   = District::orderBy('name')->get();
        $currentData = $this->getCurrentData($activeTab, $search, $pageSize);

        return view('data-management.index', [
            'activeTab'   => $activeTab,
            'search'      => $search,
            'districts'   => $districts,
            'currentData' => $currentData,
        ]);
    }

    private function getCurrentData(string $tab, string $search, int $pageSize)
    {
        return match ($tab) {
            'cases' => DivorceRecap::with('district')
                ->when($search !== '', function ($q) use ($search) {
                    $q->where('kategori', 'like', "%{$search}%")
                      ->orWhere('jenis_perkara', 'like', "%{$search}%")
                      ->orWhereHas('district', fn ($d) => $d->where('name', 'like', "%{$search}%"));
                })
                ->orderBy('year', 'desc')
                ->paginate($pageSize)
                ->withQueryString(),

            'marriage' => Marriage::with('district')
                ->when($search !== '', function ($q) use ($search) {
                    $q->whereHas('district', fn ($d) => $d->where('name', 'like', "%{$search}%"));
                })
                ->orderBy('year', 'desc')
                ->paginate($pageSize)
                ->withQueryString(),

            'population' => Population::with('district')
                ->when($search !== '', function ($q) use ($search) {
                    $q->whereHas('district', fn ($d) => $d->where('name', 'like', "%{$search}%"));
                })
                ->orderBy('year', 'desc')
                ->paginate($pageSize)
                ->withQueryString(),

            'factors' => DivorceFactorRecap::with('district')
                ->when($search !== '', function ($q) use ($search) {
                    $q->where('faktor', 'like', "%{$search}%")
                      ->orWhereHas('district', fn ($d) => $d->where('name', 'like', "%{$search}%"));
                })
                ->orderBy('year', 'desc')
                ->paginate($pageSize)
                ->withQueryString(),

            default => DivorceRecap::paginate($pageSize),
        };
    }

    public function store(Request $request): RedirectResponse
    {
        $tab = $request->input('tab');

        match ($tab) {
            'cases'      => $this->storeCase($request),
            'marriage'   => $this->storeMarriage($request),
            'population' => $this->storePopulation($request),
            'factors'    => $this->storeFactor($request),
            default      => null,
        };

        return redirect()->route('data-management.index', ['tab' => $tab])
            ->with('success', 'Data berhasil ditambahkan.');
    }

    public function update(Request $request, string $tab, int $id): RedirectResponse
    {
        match ($tab) {
            'cases'      => $this->updateCase($request, $id),
            'marriage'   => $this->updateMarriage($request, $id),
            'population' => $this->updatePopulation($request, $id),
            'factors'    => $this->updateFactor($request, $id),
            default      => null,
        };

        return redirect()->route('data-management.index', ['tab' => $tab])
            ->with('success', 'Data berhasil diperbarui.');
    }

    public function destroy(string $tab, int $id): RedirectResponse
    {
        match ($tab) {
            'cases'      => DivorceRecap::findOrFail($id)->delete(),
            'marriage'   => Marriage::findOrFail($id)->delete(),
            'population' => Population::findOrFail($id)->delete(),
            'factors'    => DivorceFactorRecap::findOrFail($id)->delete(),
            default      => null,
        };

        return redirect()->route('data-management.index', ['tab' => $tab])
            ->with('success', 'Data berhasil dihapus.');
    }

    // ===================== Store per tab =====================

    private function storeCase(Request $request): void
    {
        $data = $request->validate([
            'district_id'   => 'required|exists:districts,id',
            'year'          => 'required|integer|min:2000|max:2100',
            'kategori'      => 'required|in:Diterima,Diputus',
            'jenis_perkara' => 'required|in:Cerai Talak,Cerai Gugat,Jumlah',
            'jumlah'        => 'required|integer|min:0',
        ]);
        DivorceRecap::updateOrCreate(
            ['district_id' => $data['district_id'], 'year' => $data['year'],
             'kategori' => $data['kategori'], 'jenis_perkara' => $data['jenis_perkara']],
            ['jumlah' => $data['jumlah']]
        );
    }

    private function storeMarriage(Request $request): void
    {
        $data = $request->validate([
            'district_id' => 'required|exists:districts,id',
            'year'        => 'required|integer|min:2000|max:2100',
            'count'       => 'required|integer|min:0',
        ]);
        Marriage::updateOrCreate(
            ['district_id' => $data['district_id'], 'year' => $data['year']],
            ['count' => $data['count']]
        );
    }

    private function storePopulation(Request $request): void
    {
        $data = $request->validate([
            'district_id'           => 'required|exists:districts,id',
            'year'                  => 'required|integer|min:2000|max:2100',
            'jumlah_penduduk_ribu'  => 'required|numeric|min:0',
            'laju_pertumbuhan'      => 'nullable|numeric',
            'persentase_penduduk'   => 'nullable|numeric',
            'kepadatan_per_km2'     => 'nullable|numeric',
            'rasio_jenis_kelamin'   => 'nullable|numeric',
        ]);
        Population::updateOrCreate(
            ['district_id' => $data['district_id'], 'year' => $data['year']],
            $data
        );
    }

    private function storeFactor(Request $request): void
    {
        $data = $request->validate([
            'district_id'  => 'required|exists:districts,id',
            'year'         => 'required|integer|min:2000|max:2100',
            'faktor'       => 'required|string|max:255',
            'jumlah_kasus' => 'required|integer|min:0',
        ]);
        DivorceFactorRecap::updateOrCreate(
            ['district_id' => $data['district_id'], 'year' => $data['year'], 'faktor' => $data['faktor']],
            ['jumlah_kasus' => $data['jumlah_kasus']]
        );
    }

    // ===================== Update per tab =====================

    private function updateCase(Request $request, int $id): void
    {
        $data = $request->validate([
            'district_id'   => 'required|exists:districts,id',
            'year'          => 'required|integer|min:2000|max:2100',
            'kategori'      => 'required|in:Diterima,Diputus',
            'jenis_perkara' => 'required|in:Cerai Talak,Cerai Gugat,Jumlah',
            'jumlah'        => 'required|integer|min:0',
        ]);
        DivorceRecap::findOrFail($id)->update($data);
    }

    private function updateMarriage(Request $request, int $id): void
    {
        $data = $request->validate([
            'district_id' => 'required|exists:districts,id',
            'year'        => 'required|integer|min:2000|max:2100',
            'count'       => 'required|integer|min:0',
        ]);
        Marriage::findOrFail($id)->update($data);
    }

    private function updatePopulation(Request $request, int $id): void
    {
        $data = $request->validate([
            'district_id'          => 'required|exists:districts,id',
            'year'                 => 'required|integer|min:2000|max:2100',
            'jumlah_penduduk_ribu' => 'required|numeric|min:0',
            'laju_pertumbuhan'     => 'nullable|numeric',
            'persentase_penduduk'  => 'nullable|numeric',
            'kepadatan_per_km2'    => 'nullable|numeric',
            'rasio_jenis_kelamin'  => 'nullable|numeric',
        ]);
        Population::findOrFail($id)->update($data);
    }

    private function updateFactor(Request $request, int $id): void
    {
        $data = $request->validate([
            'district_id'  => 'required|exists:districts,id',
            'year'         => 'required|integer|min:2000|max:2100',
            'faktor'       => 'required|string|max:255',
            'jumlah_kasus' => 'required|integer|min:0',
        ]);
        DivorceFactorRecap::findOrFail($id)->update($data);
    }
}