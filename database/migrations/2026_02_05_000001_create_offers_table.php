<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            $table->unsignedInteger('payout_points')->default(0);

            $table->string('network')->index();                // zeydoo, cpx, mylead...
            $table->string('category')->nullable()->index();   // survey, lead...
            $table->string('difficulty')->nullable()->index(); // easy/medium/hard

            $table->unsignedSmallInteger('est_validation_hours')->nullable();

            // ✅ LA BONNE COLONNE
            $table->text('url');

            $table->boolean('active')->default(true)->index();
            $table->integer('sort_weight')->default(0)->index();
            $table->boolean('is_simple')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
