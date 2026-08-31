<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cld_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cld_id')->constrained('clds')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->string('statut', 20)->default('active');
            $table->timestamps();

            $table->index(['cld_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cld_user');
    }
};
