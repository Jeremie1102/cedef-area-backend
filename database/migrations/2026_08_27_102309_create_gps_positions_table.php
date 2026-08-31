<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gps_positions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('local_id')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('mission_id')->constrained('missions')->restrictOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('precision', 8, 2)->nullable();
            $table->decimal('altitude', 8, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->decimal('heading', 5, 2)->nullable();
            $table->dateTime('captured_at');
            $table->timestamps();

            $table->unique(['user_id', 'local_id']);
            $table->index(['mission_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gps_positions');
    }
};
