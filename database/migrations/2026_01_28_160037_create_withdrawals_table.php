<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->unsignedBigInteger('amount_cents');
            $table->string('currency', 3)->default('EUR');

            // PayPal only pour l’instant
            $table->string('method')->default('paypal');
            $table->string('paypal_email');

            // pending -> paid | rejected
            $table->string('status')->default('pending');

            // Admin / suivi
            $table->text('admin_note')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();

            // JSON libre (optionnel, utile plus tard)
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
