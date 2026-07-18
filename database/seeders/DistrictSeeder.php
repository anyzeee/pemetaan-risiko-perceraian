<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Kabupaten Banjarmasin',
            'Kabupaten Banjar',
            'Kabupaten Barito Kuala',
            'Kabupaten Tanah Laut',
            'Kabupaten Tapin',
            'Kabupaten Hulu Sungai Selatan',
            'Kabupaten Hulu Sungai Tengah',
            'Kabupaten Hulu Sungai Utara',
            'Kabupaten Tabalong',
            'Kabupaten Balangan',
            'Kabupaten Kotabaru',
            'Kabupaten Tanah Bumbu',
            'Kota Banjarbaru',
        ];

        foreach ($names as $name) {
            District::firstOrCreate(['name' => $name]);
        }
    }
}