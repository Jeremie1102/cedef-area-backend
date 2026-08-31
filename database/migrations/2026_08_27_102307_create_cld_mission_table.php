<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cld_mission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained('missions')->cascadeOnDelete();
            $table->foreignId('cld_id')->constrained('clds')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['mission_id', 'cld_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cld_mission');
    }
};
