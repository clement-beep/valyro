<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('offer_clicks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();

            $table->string('subid', 64)->unique();

            $table->string('ip', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referrer')->nullable();

            $table->unsignedSmallInteger('risk_score')->default(0);
            $table->json('risk_flags')->nullable();

            $table->timestamp('started_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'offer_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['ip', 'created_at']);
            $table->index(['subid']);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_clicks');
    }
};
