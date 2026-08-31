<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separe le role administratif de la fonction metier de terrain (section 5,
 * etape 10) : `fonction` (animateur/mrv/sauvegarde/sig) reste inchangee et
 * decrit le travail de terrain d'un agent ; `admin_role`, nouvelle colonne
 * independante, decrit un droit d'administration eventuel. Un compte peut
 * n'avoir aucun role admin (valeur par defaut, null), avoir une fonction de
 * terrain ET un role admin, ou etre un compte purement administratif sans
 * fonction de terrain (`fonction` est deja nullable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('admin_role', 20)->nullable()->after('fonction');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('admin_role');
        });
    }
};
