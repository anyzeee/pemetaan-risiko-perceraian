<?php

namespace Database\Seeders;

use App\Models\Recommendation;
use Illuminate\Database\Seeder;

class RecommendationSeeder extends Seeder
{
    public function run(): void
    {
        $recommendations = [
            [
                'program'              => 'Mediasi Intensif PUSPAGA',
                'description'          => 'Program mediasi keluarga intensif melalui Pusat Pembelajaran Keluarga (PUSPAGA) untuk pasangan dalam krisis rumah tangga.',
                'risk_level'           => 'critical',
                'priority'             => 'urgent',
                'implementing_agency'  => 'DP3AKB Provinsi',
                'target_population'    => 'Pasangan dalam konflik aktif',
                'estimated_duration'   => '1-3 bulan',
                'cause_factors'        => ['Perselisihan', 'KDRT', 'Ekonomi'],
                'target_districts'     => null,
            ],
            [
                'program'              => 'Program Pencegahan KDRT',
                'description'          => 'Intervensi darurat untuk kasus kekerasan dalam rumah tangga dengan pendampingan hukum dan psikologis bagi korban.',
                'risk_level'           => 'critical',
                'priority'             => 'urgent',
                'implementing_agency'  => 'DP3AKB & LBH Banjarmasin',
                'target_population'    => 'Korban KDRT dan keluarga rentan',
                'estimated_duration'   => '6 bulan',
                'cause_factors'        => ['KDRT', 'Perselisihan'],
                'target_districts'     => null,
            ],
            [
                'program'              => 'Monitoring & Konseling Berkala',
                'description'          => 'Program konseling rutin bulanan untuk pasangan yang teridentifikasi berisiko tinggi berdasarkan hasil analisis clustering.',
                'risk_level'           => 'high',
                'priority'             => 'high',
                'implementing_agency'  => 'KUA Kabupaten',
                'target_population'    => 'Pasangan berisiko tinggi',
                'estimated_duration'   => '6-12 bulan',
                'cause_factors'        => ['Ekonomi', 'Komunikasi'],
                'target_districts'     => null,
            ],
            [
                'program'              => 'Kampanye Kesadaran Komunitas',
                'description'          => 'Kampanye edukasi masyarakat tentang ketahanan keluarga, resolusi konflik, dan pentingnya peran keluarga dalam masyarakat.',
                'risk_level'           => 'high',
                'priority'             => 'high',
                'implementing_agency'  => 'Dinas Sosial & DP3AKB',
                'target_population'    => 'Masyarakat umum usia produktif',
                'estimated_duration'   => '3 bulan',
                'cause_factors'        => ['Komunikasi', 'Ekonomi'],
                'target_districts'     => null,
            ],
            [
                'program'              => 'Program Pendidikan Pra-Nikah',
                'description'          => 'Pelatihan dan bimbingan kesiapan pernikahan untuk calon pasangan suami istri sebelum akad nikah.',
                'risk_level'           => 'moderate',
                'priority'             => 'medium',
                'implementing_agency'  => 'KUA Kabupaten & Kemenag',
                'target_population'    => 'Calon pengantin',
                'estimated_duration'   => '2 minggu',
                'cause_factors'        => ['Komunikasi'],
                'target_districts'     => null,
            ],
            [
                'program'              => 'Pelatihan Ekonomi Keluarga',
                'description'          => 'Program pelatihan keterampilan ekonomi dan manajemen keuangan keluarga untuk meningkatkan ketahanan ekonomi rumah tangga.',
                'risk_level'           => 'moderate',
                'priority'             => 'medium',
                'implementing_agency'  => 'Dinas Koperasi & DP3AKB',
                'target_population'    => 'Keluarga prasejahtera',
                'estimated_duration'   => '3 bulan',
                'cause_factors'        => ['Ekonomi'],
                'target_districts'     => null,
            ],
            [
                'program'              => 'Keterlibatan Komunitas Berkelanjutan',
                'description'          => 'Program pemberdayaan keluarga berbasis komunitas untuk menjaga stabilitas dan kohesi sosial di wilayah risiko rendah.',
                'risk_level'           => 'low',
                'priority'             => 'low',
                'implementing_agency'  => 'PKK & Posyandu',
                'target_population'    => 'Keluarga di komunitas',
                'estimated_duration'   => '12 bulan',
                'cause_factors'        => [],
                'target_districts'     => null,
            ],
            [
                'program'              => 'Pendidikan Karakter Remaja',
                'description'          => 'Program penguatan karakter dan nilai keluarga bagi remaja sebagai generasi penerus, untuk pencegahan dini masalah keluarga.',
                'risk_level'           => 'low',
                'priority'             => 'low',
                'implementing_agency'  => 'Dinas Pendidikan & DP3AKB',
                'target_population'    => 'Remaja usia 15-21 tahun',
                'estimated_duration'   => '6 bulan',
                'cause_factors'        => [],
                'target_districts'     => null,
            ],
        ];

        foreach ($recommendations as $rec) {
            Recommendation::firstOrCreate(
                ['program' => $rec['program']],
                $rec
            );
        }
    }
}