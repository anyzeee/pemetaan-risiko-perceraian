<?php

if (!function_exists('riskColorClasses')) {
    /**
     * Class warna untuk kotak/tile di peta, berdasarkan level risiko.
     */
    function riskColorClasses(string $level): string
    {
        return match ($level) {
            'critical' => 'bg-red-50 border-red-200',
            'high'     => 'bg-orange-50 border-orange-200',
            'moderate' => 'bg-yellow-50 border-yellow-200',
            'low'      => 'bg-green-50 border-green-200',
            default    => 'bg-slate-50 border-slate-200',
        };
    }
}

if (!function_exists('riskBadgeClasses')) {
    /**
     * Class warna untuk badge label level risiko.
     */
    function riskBadgeClasses(string $level): string
    {
        return match ($level) {
            'critical' => 'bg-red-100 text-red-800',
            'high'     => 'bg-orange-100 text-orange-800',
            'moderate' => 'bg-yellow-100 text-yellow-800',
            'low'      => 'bg-green-100 text-green-800',
            default    => 'bg-slate-100 text-slate-800',
        };
    }
}

if (!function_exists('riskLabel')) {
    /**
     * Label teks Bahasa Indonesia untuk level risiko.
     */
    function riskLabel(string $level): string
    {
        return match ($level) {
            'critical' => 'Kritis',
            'high'     => 'Tinggi',
            'moderate' => 'Sedang',
            'low'      => 'Rendah',
            default    => 'Tidak ada data',
        };
    }
}