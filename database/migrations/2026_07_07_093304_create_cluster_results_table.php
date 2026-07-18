<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cluster_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->unsignedTinyInteger('cluster_id');          // 0,1,2,3
            $table->enum('risk_level', ['critical', 'high', 'moderate', 'low']);
            $table->unsignedTinyInteger('risk_score');           // 0-100
            $table->decimal('divorce_rate', 8, 4)->default(0);  // kasus/populasi*1000
            $table->unsignedInteger('divorce_count')->default(0);
            $table->unsignedInteger('marriage_count')->default(0);
            $table->unsignedInteger('population_count')->default(0);
            $table->decimal('factor_weight', 8, 4)->default(0); // rata-rata bobot faktor
            // Fitur ternormalisasi (0-1) yang dipakai K-Means
            $table->decimal('feat_divorce_rate', 8, 6)->default(0);
            $table->decimal('feat_divorce_count', 8, 6)->default(0);
            $table->decimal('feat_marriage_ratio', 8, 6)->default(0);
            $table->decimal('feat_population', 8, 6)->default(0);
            $table->decimal('feat_factor_weight', 8, 6)->default(0);
            $table->unsignedSmallInteger('iteration_ran')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cluster_results');
    }
};