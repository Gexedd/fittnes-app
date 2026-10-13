<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            // Nivel mínimo de plan requerido para verlo (1..3).
            $table->unsignedTinyInteger('min_plan_level')->default(1);
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            // GUID del video en Bunny Stream. Nunca se expone sin token firmado.
            $table->string('bunny_video_id')->unique();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            // Índice para el filtrado del catálogo por nivel + estado.
            $table->index(['min_plan_level', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
