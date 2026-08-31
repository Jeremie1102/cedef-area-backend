<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('type_activite')->nullable();
            $table->date('date_debut_prevue');
            $table->date('date_fin_prevue')->nullable();
            $table->string('statut', 20)->default('planned');
            $table->foreignId('sector_id')->nullable()->constrained('sectors')->nullOnDelete();
            $table->foreignId('groupement_id')->nullable()->constrained('groupements')->nullOnDelete();
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missions');
    }
};
