<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Marriage;
use App\Models\Population;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $search    = (string) $request->query('search', '');
        $pageSize  = 10;
        $districts = District::orderBy('name')->get();

        $currentData = $this->getCurrentData($activeTab, $search, $pageSize);

        return view('data-management.index', [
            'activeTab'   => $activeTab,
            'search'      => $search,
            'districts'   => $districts,
            'currentData' => $currentData,
        ]);
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function getCurrentData(string $tab, string $search, int $pageSize)
    {
        return match ($tab) {

            'cases' => $this->tableExists('divorce_recaps')
                ? DB::table('divorce_recaps')
                    ->join('districts', 'divorce_recaps.district_id', '=', 'districts.id')
                    ->select(
                        'divorce_recaps.id','divorce_recaps.district_id','divorce_recaps.year',
                        'districts.name as district_name',
                        'divorce_recaps.cerai_talak_diterima','divorce_recaps.cerai_gugat_diterima',
                        'divorce_recaps.jumlah_diterima','divorce_recaps.cerai_talak_diputus',
                        'divorce_recaps.cerai_gugat_diputus','divorce_recaps.jumlah_diputus'
                    )
                    ->when($search !== '', fn ($q) =>
                        $q->where('districts.name', 'like', "%{$search}%")
                    )
                    ->orderByDesc('divorce_recaps.year')
                    ->paginate($pageSize)
                    ->withQueryString()
                : DB::table('divorce_cases')
                    ->join('districts', 'divorce_cases.district_id', '=', 'districts.id')
                    ->select('divorce_cases.*', 'districts.name as district_name')
                    ->when($search !== '', fn ($q) =>
                        $q->where('districts.name', 'like', "%{$search}%")
                          ->orWhere('category', 'like', "%{$search}%")
                          ->orWhere('case_number', 'like', "%{$search}%")
                    )
                    ->orderByDesc('divorce_cases.year')
                    ->paginate($pageSize)
                    ->withQueryString(),

            'marriage' => DB::table('marriages')
                ->join('districts', 'marriages.district_id', '=', 'districts.id')
                ->select('marriages.*', 'districts.name as district_name')
                ->when($search !== '', fn ($q) =>
                    $q->where('districts.name', 'like', "%{$search}%")
                )
                ->orderByDesc('marriages.year')
                ->paginate($pageSize)
                ->withQueryString(),

            'population' => DB::table('populations')
                ->join('districts', 'populations.district_id', '=', 'districts.id')
                ->select('populations.*', 'districts.name as district_name')
                ->when($search !== '', fn ($q) =>
                    $q->where('districts.name', 'like', "%{$search}%")
                )
                ->orderByDesc('populations.year')
                ->paginate($pageSize)
                ->withQueryString(),

            'factors' => $this->tableExists('divorce_factor_recaps')
                ? DB::table('divorce_factor_recaps')
                    ->join('districts', 'divorce_factor_recaps.district_id', '=', 'districts.id')
                    ->select('divorce_factor_recaps.*', 'districts.name as district_name')
                    ->when($search !== '', fn ($q) =>
                        $q->where('faktor', 'like', "%{$search}%")
                          ->orWhere('districts.name', 'like', "%{$search}%")
                    )
                    ->orderByDesc('divorce_factor_recaps.year')
                    ->paginate($pageSize)
                    ->withQueryString()
                : DB::table('divorce_factors')
                    ->select('divorce_factors.*')
                    ->when($search !== '', fn ($q) =>
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('category', 'like', "%{$search}%")
                    )
                    ->paginate($pageSize)
                    ->withQueryString(),

            default => DB::table('districts')->paginate($pageSize),
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
            'cases'      => $this->tableExists('divorce_recaps')
                ? DB::table('divorce_recaps')->where('id', $id)->delete()
                : DB::table('divorce_cases')->where('id', $id)->delete(),
            'marriage'   => DB::table('marriages')->where('id', $id)->delete(),
            'population' => DB::table('populations')->where('id', $id)->delete(),
            'factors'    => $this->tableExists('divorce_factor_recaps')
                ? DB::table('divorce_factor_recaps')->where('id', $id)->delete()
                : DB::table('divorce_factors')->where('id', $id)->delete(),
            default      => null,
        };

        return redirect()->route('data-management.index', ['tab' => $tab])
            ->with('success', 'Data berhasil dihapus.');
    }

    // ===================== Store per tab =====================

    private function storeCase(Request $request): void
    {
        if ($this->tableExists('divorce_recaps')) {
            $data = $request->validate([
                'district_id'          => 'required|exists:districts,id',
                'year'                 => 'required|integer|min:2000|max:2100',
                'cerai_talak_diterima' => 'nullable|integer|min:0',
                'cerai_gugat_diterima' => 'nullable|integer|min:0',
                'jumlah_diterima'      => 'nullable|integer|min:0',
                'cerai_talak_diputus'  => 'nullable|integer|min:0',
                'cerai_gugat_diputus'  => 'nullable|integer|min:0',
                'jumlah_diputus'       => 'nullable|integer|min:0',
            ]);
            DB::table('divorce_recaps')->updateOrInsert(
                ['district_id' => $data['district_id'], 'year' => $data['year']],
                array_merge($data, ['updated_at' => now(), 'created_at' => now()])
            );
        } else {
            $data = $request->validate([
                'case_number' => 'required|string|max:255',
                'district_id' => 'required|exists:districts,id',
                'category'    => 'required|string|max:255',
                'year'        => 'required|integer|min:2000|max:2100',
            ]);
            DB::table('divorce_cases')->insert(array_merge($data, ['created_at' => now(), 'updated_at' => now()]));
        }
    }

    private function storeMarriage(Request $request): void
    {
        $data = $request->validate([
            'district_id' => 'required|exists:districts,id',
            'year'        => 'required|integer|min:2000|max:2100',
            'count'       => 'required|integer|min:0',
        ]);
        DB::table('marriages')->updateOrInsert(
            ['district_id' => $data['district_id'], 'year' => $data['year']],
            ['count' => $data['count'], 'updated_at' => now(), 'created_at' => now()]
        );
    }

    private function storePopulation(Request $request): void
    {
        $cols = DB::getSchemaBuilder()->getColumnListing('populations');

        if (in_array('jumlah_penduduk_ribu', $cols)) {
            $data = $request->validate([
                'district_id'          => 'required|exists:districts,id',
                'year'                 => 'required|integer|min:2000|max:2100',
                'jumlah_penduduk_ribu' => 'required|numeric|min:0',
                'laju_pertumbuhan'     => 'nullable|numeric',
                'persentase_penduduk'  => 'nullable|numeric',
                'kepadatan_per_km2'    => 'nullable|numeric',
                'rasio_jenis_kelamin'  => 'nullable|numeric',
            ]);
            DB::table('populations')->updateOrInsert(
                ['district_id' => $data['district_id'], 'year' => $data['year']],
                array_merge($data, ['updated_at' => now(), 'created_at' => now()])
            );
        } else {
            $data = $request->validate([
                'district_id' => 'required|exists:districts,id',
                'year'        => 'required|integer|min:2000|max:2100',
                'count'       => 'required|integer|min:0',
            ]);
            DB::table('populations')->updateOrInsert(
                ['district_id' => $data['district_id'], 'year' => $data['year']],
                ['count' => $data['count'], 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    private function storeFactor(Request $request): void
    {
        if ($this->tableExists('divorce_factor_recaps')) {
            $data = $request->validate([
                'district_id'  => 'required|exists:districts,id',
                'year'         => 'required|integer|min:2000|max:2100',
                'faktor'       => 'required|string|max:255',
                'jumlah_kasus' => 'required|integer|min:0',
            ]);
            DB::table('divorce_factor_recaps')->updateOrInsert(
                ['district_id' => $data['district_id'], 'year' => $data['year'], 'faktor' => $data['faktor']],
                ['jumlah_kasus' => $data['jumlah_kasus'], 'updated_at' => now(), 'created_at' => now()]
            );
        } else {
            $data = $request->validate([
                'name'     => 'required|string|max:255',
                'category' => 'required|string|max:255',
                'weight'   => 'required|integer|min:0|max:100',
            ]);
            DB::table('divorce_factors')->insert(array_merge($data, ['created_at' => now(), 'updated_at' => now()]));
        }
    }

    // ===================== Update per tab =====================

    private function updateCase(Request $request, int $id): void
    {
        if ($this->tableExists('divorce_recaps')) {
            $data = $request->validate([
                'district_id'          => 'required|exists:districts,id',
                'year'                 => 'required|integer|min:2000|max:2100',
                'cerai_talak_diterima' => 'nullable|integer|min:0',
                'cerai_gugat_diterima' => 'nullable|integer|min:0',
                'jumlah_diterima'      => 'nullable|integer|min:0',
                'cerai_talak_diputus'  => 'nullable|integer|min:0',
                'cerai_gugat_diputus'  => 'nullable|integer|min:0',
                'jumlah_diputus'       => 'nullable|integer|min:0',
            ]);
            // Hanya update nilai, tidak ubah district_id dan year
            unset($data['district_id'], $data['year']);
            DB::table('divorce_recaps')->where('id', $id)->update(array_merge($data, ['updated_at' => now()]));
        } else {
            $data = $request->validate([
                'case_number' => 'required|string|max:255',
                'district_id' => 'required|exists:districts,id',
                'category'    => 'required|string|max:255',
                'year'        => 'required|integer|min:2000|max:2100',
            ]);
            DB::table('divorce_cases')->where('id', $id)->update(array_merge($data, ['updated_at' => now()]));
        }
    }

    private function updateMarriage(Request $request, int $id): void
    {
        $data = $request->validate([
            'district_id' => 'required|exists:districts,id',
            'year'        => 'required|integer|min:2000|max:2100',
            'count'       => 'required|integer|min:0',
        ]);
        // Hanya update count — district_id dan year tidak diubah (UNIQUE constraint)
        DB::table('marriages')->where('id', $id)->update([
            'count'      => $data['count'],
            'updated_at' => now(),
        ]);
    }

    private function updatePopulation(Request $request, int $id): void
    {
        $cols = DB::getSchemaBuilder()->getColumnListing('populations');

        if (in_array('jumlah_penduduk_ribu', $cols)) {
            $data = $request->validate([
                'district_id'          => 'required|exists:districts,id',
                'year'                 => 'required|integer|min:2000|max:2100',
                'jumlah_penduduk_ribu' => 'required|numeric|min:0',
                'laju_pertumbuhan'     => 'nullable|numeric',
                'persentase_penduduk'  => 'nullable|numeric',
                'kepadatan_per_km2'    => 'nullable|numeric',
                'rasio_jenis_kelamin'  => 'nullable|numeric',
            ]);
        } else {
            $data = $request->validate([
                'district_id' => 'required|exists:districts,id',
                'year'        => 'required|integer|min:2000|max:2100',
                'count'       => 'required|integer|min:0',
            ]);
        }
        // Jangan update district_id dan year (penyebab UNIQUE constraint)
        unset($data['district_id'], $data['year']);
        DB::table('populations')->where('id', $id)->update(array_merge($data, ['updated_at' => now()]));
    }

    private function updateFactor(Request $request, int $id): void
    {
        if ($this->tableExists('divorce_factor_recaps')) {
            $data = $request->validate([
                'district_id'  => 'required|exists:districts,id',
                'year'         => 'required|integer|min:2000|max:2100',
                'faktor'       => 'required|string|max:255',
                'jumlah_kasus' => 'required|integer|min:0',
            ]);
            DB::table('divorce_factor_recaps')->where('id', $id)->update(array_merge($data, ['updated_at' => now()]));
        } else {
            $data = $request->validate([
                'name'     => 'required|string|max:255',
                'category' => 'required|string|max:255',
                'weight'   => 'required|integer|min:0|max:100',
            ]);
            DB::table('divorce_factors')->where('id', $id)->update(array_merge($data, ['updated_at' => now()]));
        }
    }
}