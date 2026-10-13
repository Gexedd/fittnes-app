<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            // ID del pago en Mercado Pago.
            $table->string('mp_payment_id')->unique();
            $table->unsignedInteger('amount_clp');
            $table->string('status');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // Registro de webhooks recibidos, para garantizar idempotencia
        // (un mismo evento no debe procesarse dos veces).
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('event_id');
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('payments');
    }
};
