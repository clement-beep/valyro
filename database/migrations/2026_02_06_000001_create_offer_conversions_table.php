<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('offer_conversions', function (Blueprint $table) {
            $table->id();

            // idempotence forte (anti double credit)
            $table->string('external_id', 128)->unique();

            $table->string('subid', 64)->index();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('offer_click_id')->nullable()->constrained('offer_clicks')->nullOnDelete();

            $table->string('network')->nullable()->index(); // bitlabs, etc.
            $table->string('status', 32)->index();          // pending|confirmed|rejected
            $table->unsignedInteger('payout_points')->default(0);

            $table->string('ip', 64)->nullable();
            $table->text('user_agent')->nullable();

            $table->json('raw')->nullable();

            $table->timestamp('received_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'offer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_conversions');
    }
};
