<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    /**
     * Alias nama kota tempat Pengadilan Agama berada → nama kabupaten/kota resmi di database.
     * File sumber data sering memakai nama ibu kota kabupaten/nama kota PA
     * (mis. "Amuntai"), bukan nama kabupaten resmi (mis. "Kabupaten Hulu Sungai Utara").
     */
    private function districtAliases(): array
    {
        return [
            'banjarmasin'            => 'Kota Banjarmasin',
            'banjarbaru'             => 'Kota Banjarbaru',
            'kota banjar baru'       => 'Kota Banjarbaru',
            'martapura'              => 'Kabupaten Banjar',
            'banjar'                 => 'Kabupaten Banjar',
            'marabahan'              => 'Kabupaten Barito Kuala',
            'barito kuala'           => 'Kabupaten Barito Kuala',
            'pelaihari'              => 'Kabupaten Tanah Laut',
            'tanah laut'             => 'Kabupaten Tanah Laut',
            'rantau'                 => 'Kabupaten Tapin',
            'tapin'                  => 'Kabupaten Tapin',
            'kandangan'              => 'Kabupaten Hulu Sungai Selatan',
            'hulu sungai selatan'    => 'Kabupaten Hulu Sungai Selatan',
            'barabai'                => 'Kabupaten Hulu Sungai Tengah',
            'hulu sungai tengah'     => 'Kabupaten Hulu Sungai Tengah',
            'amuntai'                => 'Kabupaten Hulu Sungai Utara',
            'hulu sungai utara'      => 'Kabupaten Hulu Sungai Utara',
            'tanjung'                => 'Kabupaten Tabalong',
            'tabalong'               => 'Kabupaten Tabalong',
            'negara'                 => 'Kabupaten Balangan',
            'balangan'               => 'Kabupaten Balangan',
            'kotabaru'               => 'Kabupaten Kotabaru',
            'kota baru'              => 'Kabupaten Kotabaru',
            'batulicin'              => 'Kabupaten Tanah Bumbu',
            'tanah bumbu'            => 'Kabupaten Tanah Bumbu',
        ];
    }

    /**
     * Mapping nama pengadilan/kabupaten di file → district_id di database.
     */
    private function getDistrictMap(): array
    {
        $map = [];
        foreach (DB::table('districts')->get() as $d) {
            // nama penuh: "kota banjarmasin"
            $map[strtolower($d->name)] = $d->id;
            // tanpa prefix: "banjarmasin", "tanah laut"
            $short = strtolower(preg_replace('/^(Kabupaten |Kota |Pengadilan Agama |PA )/i', '', $d->name));
            $map[$short] = $d->id;
        }

        // Tambahkan alias nama kota PA (Amuntai, Martapura, dst) ke map yang sama
        $byName = [];
        foreach (DB::table('districts')->get() as $d) {
            $byName[$d->name] = $d->id;
        }
        foreach ($this->districtAliases() as $alias => $officialName) {
            if (isset($byName[$officialName])) {
                $map[$alias] = $byName[$officialName];
            }
        }

        return $map;
    }

    private function resolveDistrictId(string $name, array $map): ?int
    {
        $name = trim($name);
        $candidates = [
            strtolower($name),
            strtolower(preg_replace('/^(Kabupaten |Kota |Pengadilan Agama |PA |Kab\. |Kab )/i', '', $name)),
        ];

        foreach ($candidates as $c) {
            if (isset($map[$c])) return $map[$c];
        }

        // fuzzy: cari substring match
        foreach ($map as $key => $id) {
            if (str_contains(strtolower($name), $key) || str_contains($key, strtolower($name))) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Baca file CSV atau Excel, return array of rows (tanpa header).
     *
     * PENTING: $ext HARUS dikirim dari nama file ASLI (getClientOriginalExtension()),
     * bukan ditebak dari $path — karena $path adalah file temporary upload PHP
     * (mis. "phpA5AA.tmp") yang tidak punya ekstensi asli.
     */
    private function readFile(string $path, string $ext = ''): array
    {
        $ext = strtolower($ext ?: pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($ext, ['xlsx', 'xls', 'ods'])) {
            $spreadsheet = IOFactory::load($path);
            $sheet       = $spreadsheet->getActiveSheet();
            $allRows     = $sheet->toArray(null, true, true, false);

            // Skip baris header (baris pertama) dan baris kosong
            $result = [];
            $isFirst = true;
            foreach ($allRows as $row) {
                // Skip baris header
                if ($isFirst) { $isFirst = false; continue; }

                // Ambil nilai dari kolom yang ada isinya
                $values = array_values(array_filter($row, fn ($v) => $v !== null && $v !== ''));

                // Minimal 2 kolom berisi data
                if (count($values) >= 2) {
                    // Rebuild dari awal — ambil semua kolom berurutan dari kolom pertama yang berisi data
                    $startCol = 0;
                    foreach ($row as $ci => $val) {
                        if ($val !== null && $val !== '') { $startCol = $ci; break; }
                    }
                    $result[] = array_values(array_slice($row, $startCol));
                }
            }
            return $result;
        }

        // Baca CSV
        $rows   = [];
        $handle = fopen($path, 'r');
        $header = null;
        $delim  = $this->detectDelimiter($path);

        while (($row = fgetcsv($handle, 0, $delim)) !== false) {
            if (!$header) { $header = $row; continue; } // skip header
            if (!empty(array_filter($row))) {
                $rows[] = $row;
            }
        }
        fclose($handle);
        return $rows;
    }

    private function detectDelimiter(string $path): string
    {
        $line = fgets(fopen($path, 'r'));
        return substr_count($line, "\t") > substr_count($line, ',') ? "\t" : ',';
    }

    private function cleanNumber(mixed $val): string
    {
        $str = trim((string) $val);
        // Deteksi format: kalau ada koma setelah titik (1.000,5) = format eropa
        // kalau ada titik setelah koma (1,000.5) = format inggris
        if (preg_match('/\.\d{3},/', $str)) {
            // Format eropa: 1.234,56 → hapus titik, ganti koma jadi titik
            $str = str_replace(['.', ','], ['', '.'], $str);
        } elseif (preg_match('/,\d{3}\./', $str)) {
            // Format inggris: 1,234.56 → hapus koma saja
            $str = str_replace(',', '', $str);
        } elseif (preg_match('/,/', $str) && !preg_match('/\./', $str)) {
            // Hanya ada koma: 354,3 → ganti koma jadi titik
            $str = str_replace(',', '.', $str);
        }
        // Kalau hanya ada titik (354.3) → biarkan apa adanya
        return $str;
    }

    /**
     * Import Rekapitulasi Perceraian
     * Kolom CSV: Pengadilan Agama | Tahun | Kategori | Jenis Perkara | Jumlah
     * (format long — beberapa baris per Pengadilan Agama+Tahun)
     *
     * Tabel divorce_recaps berformat wide (1 baris per Pengadilan Agama+Tahun),
     * jadi baris-baris CSV di-pivot/dikelompokkan dulu sebelum disimpan.
     */
    public function importDivorceRecaps(Request $request): RedirectResponse
    {
        if (!$request->hasFile('file')) {
            return redirect()->back()->with('success', 'ERROR: Tidak ada file yang diupload.');
        }

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        \Illuminate\Support\Facades\Log::info('IMPORT DEBUG', [
            'original_name' => $file->getClientOriginalName(),
            'extension'     => $ext,
            'size'          => $file->getSize(),
            'real_path'     => $path,
            'mime'          => $file->getMimeType(),
        ]);

        $map  = $this->getDistrictMap();
        $rows = $this->readFile($path, $ext);

        \Illuminate\Support\Facades\Log::info('IMPORT DEBUG - raw rows sample', [
            'total_rows' => count($rows),
            'first_5'    => array_slice($rows, 0, 5),
        ]);

        $success = 0;
        $skip    = 0;
        $buffer  = [];

        foreach ($rows as $i => $row) {
            [$pa, $tahun, $kategori, $jenis, $jumlah] = array_pad(array_values($row), 5, null);

            $districtId = $this->resolveDistrictId((string) $pa, $map);
            $col        = $districtId ? $this->mapKategoriJenis(trim((string) $kategori), trim((string) $jenis)) : null;

            if (!$districtId || !$tahun || !$col) {
                \Illuminate\Support\Facades\Log::info("IMPORT DEBUG - SKIP row {$i}", [
                    'pa' => $pa, 'tahun' => $tahun, 'kategori' => $kategori, 'jenis' => $jenis, 'jumlah' => $jumlah,
                    'districtId_found' => $districtId,
                    'col_found' => $col,
                ]);
                $skip++;
                continue;
            }

            $jumlahClean = (int) $this->cleanNumber($jumlah);
            $key         = "{$districtId}_{$tahun}";

            $buffer[$key]['district_id'] = $districtId;
            $buffer[$key]['year']        = (int) $tahun;
            $buffer[$key][$col]          = $jumlahClean;
            $success++;
        }

        foreach ($buffer as $data) {
            $districtId = $data['district_id'];
            $year       = $data['year'];
            unset($data['district_id'], $data['year']);

            DB::table('divorce_recaps')->updateOrInsert(
                ['district_id' => $districtId, 'year' => $year],
                array_merge($data, ['updated_at' => now(), 'created_at' => now()])
            );
        }

        return redirect()->route('data-management.index', ['tab' => 'cases'])
            ->with('success', "Import selesai: {$success} baris berhasil, {$skip} dilewati.");
    }

    /**
     * Petakan kombinasi Kategori + Jenis Perkara ke nama kolom di tabel divorce_recaps.
     */
    private function mapKategoriJenis(string $kategori, string $jenis): ?string
    {
        $kategori = strtolower($kategori);
        $jenis    = strtolower($jenis);

        $map = [
            'diterima' => [
                'cerai talak' => 'cerai_talak_diterima',
                'cerai gugat' => 'cerai_gugat_diterima',
                'jumlah'      => 'jumlah_diterima',
            ],
            'diputus' => [
                'cerai talak' => 'cerai_talak_diputus',
                'cerai gugat' => 'cerai_gugat_diputus',
                'jumlah'      => 'jumlah_diputus',
            ],
        ];

        return $map[$kategori][$jenis] ?? null;
    }

    /**
     * Import Faktor Perceraian
     * Kolom: Pengadilan Agama | Tahun | Faktor Penyebab | Jumlah Kasus
     */
    public function importDivorceFactors(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file']);

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        $map     = $this->getDistrictMap();
        $rows    = $this->readFile($file->getRealPath(), $ext);
        $success = 0;
        $skip    = 0;

        foreach ($rows as $row) {
            [$pa, $tahun, $faktor, $jumlah] = array_pad(array_values($row), 4, null);

            $districtId = $this->resolveDistrictId((string) $pa, $map);
            if (!$districtId || !$tahun || !$faktor) { $skip++; continue; }

            DB::table('divorce_factor_recaps')->updateOrInsert(
                [
                    'district_id' => $districtId,
                    'year'        => (int) $tahun,
                    'faktor'      => trim((string) $faktor),
                ],
                ['jumlah_kasus' => (int) $this->cleanNumber($jumlah), 'updated_at' => now(), 'created_at' => now()]
            );
            $success++;
        }

        return redirect()->route('data-management.index', ['tab' => 'factors'])
            ->with('success', "Import selesai: {$success} baris berhasil, {$skip} dilewati.");
    }

    /**
     * Import Data Pernikahan
     * Kolom: Kabupaten/Kota | Tahun | Jumlah Nikah
     */
    public function importMarriages(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file']);

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        $map     = $this->getDistrictMap();
        $rows    = $this->readFile($file->getRealPath(), $ext);
        $success = 0;
        $skip    = 0;

        foreach ($rows as $row) {
            [$kab, $tahun, $jumlah] = array_pad(array_values($row), 3, null);

            $districtId = $this->resolveDistrictId((string) $kab, $map);
            if (!$districtId || !$tahun) { $skip++; continue; }

            DB::table('marriages')->updateOrInsert(
                ['district_id' => $districtId, 'year' => (int) $tahun],
                ['count' => (int) $this->cleanNumber($jumlah), 'updated_at' => now(), 'created_at' => now()]
            );
            $success++;
        }

        return redirect()->route('data-management.index', ['tab' => 'marriage'])
            ->with('success', "Import selesai: {$success} baris berhasil, {$skip} dilewati.");
    }

    /**
     * Import Data Populasi
     * Kolom: Kabupaten/Kota | Tahun | Indikator | Nilai
     */
    public function importPopulations(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file']);

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        $map     = $this->getDistrictMap();
        $rows    = $this->readFile($file->getRealPath(), $ext);
        $success = 0;
        $skip    = 0;
        $buffer  = [];

        foreach ($rows as $row) {
            [$kab, $tahun, $indikator, $nilai] = array_pad(array_values($row), 4, null);

            $districtId = $this->resolveDistrictId((string) $kab, $map);
            if (!$districtId || !$tahun) { $skip++; continue; }

            $col = $this->mapIndikator(trim((string) $indikator));
            if (!$col) { $skip++; continue; }

            $nilaiClean = (float) $this->cleanNumber($nilai);
            $key        = "{$districtId}_{$tahun}";

            $buffer[$key]['district_id'] = $districtId;
            $buffer[$key]['year']        = (int) $tahun;
            $buffer[$key][$col]          = $nilaiClean;
            $success++;
        }

        foreach ($buffer as $data) {
            $districtId = $data['district_id'];
            $year       = $data['year'];
            unset($data['district_id'], $data['year']);

            DB::table('populations')->updateOrInsert(
                ['district_id' => $districtId, 'year' => $year],
                array_merge($data, ['updated_at' => now(), 'created_at' => now()])
            );
        }

        return redirect()->route('data-management.index', ['tab' => 'population'])
            ->with('success', "Import selesai: {$success} baris berhasil, {$skip} dilewati.");
    }

    private function mapIndikator(string $indikator): ?string
    {
        $map = [
            'Jumlah Penduduk (Ribu)'                  => 'jumlah_penduduk_ribu',
            'Laju Pertumbuhan Penduduk per Tahun'      => 'laju_pertumbuhan',
            'Persentase Penduduk'                      => 'persentase_penduduk',
            'Kepadatan Penduduk per km persegi (Km2)'  => 'kepadatan_per_km2',
            'Rasio Jenis Kelamin Penduduk'             => 'rasio_jenis_kelamin',
        ];
        return $map[$indikator] ?? null;
    }
}