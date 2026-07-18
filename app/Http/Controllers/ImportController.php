<?php

namespace App\Http\Controllers;

use App\Models\DivorceFactorRecap;
use App\Models\DivorceRecap;
use App\Models\District;
use App\Models\Marriage;
use App\Models\Population;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    /**
     * Mapping nama pengadilan/kabupaten di CSV → district_id di database.
     * Sesuaikan kalau nama di CSV berbeda dengan nama di tabel districts.
     */
    private function getDistrictMap(): array
    {
        return District::all()->mapWithKeys(function ($d) {
            // Normalisasi: hapus "Kabupaten ", "Kota ", buat lowercase untuk matching fleksibel
            $key = strtolower(str_replace(['Kabupaten ', 'Kota '], '', $d->name));
            return [$key => $d->id];
        })->all();
    }

    private function resolveDistrictId(string $name, array $map): ?int
    {
        $normalized = strtolower(str_replace(['Kabupaten ', 'Kota ', 'Pengadilan Agama '], '', trim($name)));
        return $map[$normalized] ?? null;
    }

    /**
     * Import rekapitulasi perceraian
     * Format CSV: Pengadilan Agama | Tahun | Kategori | Jenis Perkara | Jumlah
     */
    public function importDivorceRecaps(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $map     = $this->getDistrictMap();
        $handle  = fopen($request->file('file')->getRealPath(), 'r');
        $header  = fgetcsv($handle, 0, "\t") ?: fgetcsv($handle); // coba tab dulu, lalu comma
        rewind($handle);
        $header  = null;
        $success = 0;
        $skip    = 0;

        while (($row = fgetcsv($handle, 0, $this->detectDelimiter($request->file('file')->getRealPath()))) !== false) {
            if (!$header) { $header = $row; continue; } // skip header

            [$pa, $tahun, $kategori, $jenis, $jumlah] = array_pad($row, 5, null);

            $districtId = $this->resolveDistrictId((string) $pa, $map);
            if (!$districtId) { $skip++; continue; }

            $jumlah = (int) str_replace(['.', ',', ' '], '', (string) $jumlah);

            DivorceRecap::updateOrCreate(
                [
                    'district_id'  => $districtId,
                    'year'         => (int) $tahun,
                    'kategori'     => trim($kategori),
                    'jenis_perkara'=> trim($jenis),
                ],
                ['jumlah' => $jumlah]
            );
            $success++;
        }

        fclose($handle);

        return redirect()->route('data-management.index', ['tab' => 'cases'])
            ->with('success', "Import selesai: {$success} baris berhasil, {$skip} baris dilewati.");
    }

    /**
     * Import faktor perceraian
     * Format CSV: Pengadilan Agama | Tahun | Faktor Penyebab | Jumlah Kasus
     */
    public function importDivorceFactors(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $map     = $this->getDistrictMap();
        $handle  = fopen($request->file('file')->getRealPath(), 'r');
        $header  = null;
        $success = 0;
        $skip    = 0;
        $delim   = $this->detectDelimiter($request->file('file')->getRealPath());

        while (($row = fgetcsv($handle, 0, $delim)) !== false) {
            if (!$header) { $header = $row; continue; }

            [$pa, $tahun, $faktor, $jumlah] = array_pad($row, 4, null);

            $districtId = $this->resolveDistrictId((string) $pa, $map);
            if (!$districtId) { $skip++; continue; }

            $jumlah = (int) str_replace(['.', ',', ' '], '', (string) $jumlah);

            DivorceFactorRecap::updateOrCreate(
                [
                    'district_id' => $districtId,
                    'year'        => (int) $tahun,
                    'faktor'      => trim($faktor),
                ],
                ['jumlah_kasus' => $jumlah]
            );
            $success++;
        }

        fclose($handle);

        return redirect()->route('data-management.index', ['tab' => 'factors'])
            ->with('success', "Import selesai: {$success} baris berhasil, {$skip} baris dilewati.");
    }

    /**
     * Import data pernikahan
     * Format CSV: Kabupaten/Kota | Tahun | Jumlah Nikah
     */
    public function importMarriages(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $map     = $this->getDistrictMap();
        $handle  = fopen($request->file('file')->getRealPath(), 'r');
        $header  = null;
        $success = 0;
        $skip    = 0;
        $delim   = $this->detectDelimiter($request->file('file')->getRealPath());

        while (($row = fgetcsv($handle, 0, $delim)) !== false) {
            if (!$header) { $header = $row; continue; }

            [$kab, $tahun, $jumlah] = array_pad($row, 3, null);

            $districtId = $this->resolveDistrictId((string) $kab, $map);
            if (!$districtId) { $skip++; continue; }

            $jumlah = (int) str_replace(['.', ',', ' '], '', (string) $jumlah);

            Marriage::updateOrCreate(
                ['district_id' => $districtId, 'year' => (int) $tahun],
                ['count' => $jumlah]
            );
            $success++;
        }

        fclose($handle);

        return redirect()->route('data-management.index', ['tab' => 'marriage'])
            ->with('success', "Import selesai: {$success} baris berhasil, {$skip} baris dilewati.");
    }

    /**
     * Import data populasi
     * Format CSV: Kabupaten/Kota | Tahun | Indikator | Nilai
     * Indikator yang didukung:
     *   "Jumlah Penduduk (Ribu)" → jumlah_penduduk_ribu
     *   "Laju Pertumbuhan Penduduk per Tahun" → laju_pertumbuhan
     *   "Persentase Penduduk" → persentase_penduduk
     *   "Kepadatan Penduduk per km persegi (Km2)" → kepadatan_per_km2
     *   "Rasio Jenis Kelamin Penduduk" → rasio_jenis_kelamin
     */
    public function importPopulations(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $map     = $this->getDistrictMap();
        $handle  = fopen($request->file('file')->getRealPath(), 'r');
        $header  = null;
        $success = 0;
        $skip    = 0;
        $delim   = $this->detectDelimiter($request->file('file')->getRealPath());

        // Buffer: kumpulkan dulu per district+year sebelum upsert
        $buffer = [];

        while (($row = fgetcsv($handle, 0, $delim)) !== false) {
            if (!$header) { $header = $row; continue; }

            [$kab, $tahun, $indikator, $nilai] = array_pad($row, 4, null);

            $districtId = $this->resolveDistrictId((string) $kab, $map);
            if (!$districtId) { $skip++; continue; }

            $key   = "{$districtId}_{$tahun}";
            $col   = $this->mapIndikator(trim((string) $indikator));
            $nilai = (float) str_replace([','], ['.'], str_replace('.', '', (string) $nilai));

            if ($col) {
                $buffer[$key]['district_id'] = $districtId;
                $buffer[$key]['year']        = (int) $tahun;
                $buffer[$key][$col]          = $nilai;
                $success++;
            } else {
                $skip++;
            }
        }

        fclose($handle);

        foreach ($buffer as $data) {
            $districtId = $data['district_id'];
            $year       = $data['year'];
            unset($data['district_id'], $data['year']);

            Population::updateOrCreate(
                ['district_id' => $districtId, 'year' => $year],
                $data
            );
        }

        return redirect()->route('data-management.index', ['tab' => 'population'])
            ->with('success', "Import selesai: {$success} baris berhasil, {$skip} baris dilewati.");
    }

    private function mapIndikator(string $indikator): ?string
    {
        $map = [
            'Jumlah Penduduk (Ribu)'                    => 'jumlah_penduduk_ribu',
            'Laju Pertumbuhan Penduduk per Tahun'        => 'laju_pertumbuhan',
            'Persentase Penduduk'                        => 'persentase_penduduk',
            'Kepadatan Penduduk per km persegi (Km2)'   => 'kepadatan_per_km2',
            'Rasio Jenis Kelamin Penduduk'               => 'rasio_jenis_kelamin',
        ];
        return $map[$indikator] ?? null;
    }

    /**
     * Deteksi delimiter file CSV (tab atau comma).
     */
    private function detectDelimiter(string $path): string
    {
        $line = fgets(fopen($path, 'r'));
        return substr_count($line, "\t") > substr_count($line, ',') ? "\t" : ',';
    }
}