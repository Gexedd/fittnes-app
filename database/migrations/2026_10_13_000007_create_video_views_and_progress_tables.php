<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cada evento de reproducción (para métricas de "videos más vistos").
        Schema::create('video_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('watched_seconds')->default(0);
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['video_id', 'created_at']);
        });

        // Punto de reanudación por usuario+video (una fila por par).
        Schema::create('video_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position_seconds')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'video_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_progress');
        Schema::dropIfExists('video_views');
    }
};
