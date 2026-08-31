<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('local_id')->nullable();
            $table->foreignId('media_batch_id')->constrained('media_batches')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->string('statut', 20)->default('synced');
            $table->timestamps();

            $table->unique(['media_batch_id', 'local_id']);
            $table->index(['media_batch_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_items');
    }
};
