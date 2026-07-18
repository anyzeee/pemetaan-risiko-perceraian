<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('program');
            $table->text('description');
            $table->enum('risk_level', ['critical', 'high', 'moderate', 'low']);
            $table->enum('priority', ['urgent', 'high', 'medium', 'low']);
            $table->string('implementing_agency');
            $table->string('target_population');
            $table->string('estimated_duration')->nullable(); // contoh: "3 bulan"
            $table->json('cause_factors')->nullable();        // faktor penyebab
            $table->json('target_districts')->nullable();     // kabupaten sasaran (array id)
            $table->boolean('is_completed')->default(false);
            $table->date('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};