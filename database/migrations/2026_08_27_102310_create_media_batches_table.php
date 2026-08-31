<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('local_id')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('mission_id')->constrained('missions')->restrictOnDelete();
            $table->foreignId('cld_id')->nullable()->constrained('clds')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->string('activite')->nullable();
            $table->text('description')->nullable();
            $table->date('date_activite')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('precision', 8, 2)->nullable();
            $table->string('statut', 20)->default('synced');
            $table->timestamps();

            $table->unique(['user_id', 'local_id']);
            $table->index('mission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_batches');
    }
};
