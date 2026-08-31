<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_village', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained('missions')->cascadeOnDelete();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['mission_id', 'village_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_village');
    }
};
