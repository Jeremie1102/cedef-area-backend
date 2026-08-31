<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nom')->after('id');
            $table->string('postnom')->nullable()->after('nom');
            $table->string('prenom')->nullable()->after('postnom');
            $table->string('fonction', 20)->nullable()->after('email');
            $table->string('photo_profil')->nullable()->after('fonction');
            $table->boolean('actif')->default(true)->after('photo_profil');
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->dropColumn(['nom', 'postnom', 'prenom', 'fonction', 'photo_profil', 'actif']);
        });
    }
};
