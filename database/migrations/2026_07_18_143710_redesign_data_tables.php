<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // =============================================
        // 1. divorce_cases → divorce_recaps
        //    Data agregat per pengadilan per tahun
        //    (bukan per kasus individual)
        // =============================================
        Schema::dropIfExists('divorce_cases');
        Schema::create('divorce_recaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->enum('kategori', ['Diterima', 'Diputus']);
            $table->enum('jenis_perkara', ['Cerai Talak', 'Cerai Gugat', 'Jumlah']);
            $table->unsignedInteger('jumlah')->default(0);
            $table->timestamps();

            $table->unique(['district_id', 'year', 'kategori', 'jenis_perkara']);
        });

        // =============================================
        // 2. divorce_factors → divorce_factor_recaps
        //    Jumlah kasus per faktor per wilayah per tahun
        // =============================================
        Schema::dropIfExists('divorce_factors');
        Schema::create('divorce_factor_recaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('faktor');   // Zina, KDRT, Ekonomi, dst
            $table->unsignedInteger('jumlah_kasus')->default(0);
            $table->timestamps();

            $table->unique(['district_id', 'year', 'faktor']);
        });

        // =============================================
        // 3. marriages → sudah OK, hanya tambah unique constraint
        // =============================================
        Schema::table('marriages', function (Blueprint $table) {
            $table->unique(['district_id', 'year']);
        });

        // =============================================
        // 4. populations → redesign untuk multi-indikator
        // =============================================
        Schema::dropIfExists('populations');
        Schema::create('populations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('jumlah_penduduk_ribu', 10, 2)->default(0);   // dalam ribu
            $table->decimal('laju_pertumbuhan', 8, 4)->nullable();         // % per tahun
            $table->decimal('persentase_penduduk', 8, 4)->nullable();      // % dari total Kalsel
            $table->decimal('kepadatan_per_km2', 10, 2)->nullable();       // jiwa/km2
            $table->decimal('rasio_jenis_kelamin', 8, 2)->nullable();      // laki/perempuan
            $table->timestamps();

            $table->unique(['district_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('divorce_recaps');
        Schema::dropIfExists('divorce_factor_recaps');

        Schema::table('marriages', function (Blueprint $table) {
            $table->dropUnique(['district_id', 'year']);
        });

        Schema::dropIfExists('populations');
        Schema::create('populations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('count');
            $table->timestamps();
        });
    }
};