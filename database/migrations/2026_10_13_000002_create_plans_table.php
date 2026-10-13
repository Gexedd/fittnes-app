<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // Nivel de acceso: 1 = Básico, 2 = Medio, 3 = Premium.
            // Es la clave que compara la Policy para permitir ver un video.
            $table->unsignedTinyInteger('level')->unique();
            // CLP es una moneda SIN decimales -> entero.
            $table->unsignedInteger('price_clp');
            // ID del plan en Mercado Pago (preapproval_plan). Nullable: en local
            // puede no existir todavía.
            $table->string('mp_plan_id')->nullable()->unique();
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
