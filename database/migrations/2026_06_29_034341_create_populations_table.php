<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('populations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('count');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('populations');
    }
};