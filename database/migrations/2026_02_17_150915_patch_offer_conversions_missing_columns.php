<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('offer_conversions', function (Blueprint $table) {

            // ✅ Ajouts "safe" uniquement si la colonne n'existe pas
            if (!Schema::hasColumn('offer_conversions', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('subid')->constrained()->nullOnDelete();
            }

            if (!Schema::hasColumn('offer_conversions', 'offer_id')) {
                $table->foreignId('offer_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            }

            if (!Schema::hasColumn('offer_conversions', 'offer_click_id')) {
                $table->foreignId('offer_click_id')->nullable()->after('offer_id')->constrained('offer_clicks')->nullOnDelete();
            }

            if (!Schema::hasColumn('offer_conversions', 'network')) {
                $table->string('network')->nullable()->index()->after('offer_click_id');
            }

            if (!Schema::hasColumn('offer_conversions', 'status')) {
                $table->string('status', 32)->nullable()->index()->after('network');
            }

            if (!Schema::hasColumn('offer_conversions', 'payout_points')) {
                $table->unsignedInteger('payout_points')->default(0)->after('status');
            }

            if (!Schema::hasColumn('offer_conversions', 'ip')) {
                $table->string('ip', 64)->nullable()->after('payout_points');
            }

            if (!Schema::hasColumn('offer_conversions', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip');
            }

            if (!Schema::hasColumn('offer_conversions', 'raw')) {
                $table->json('raw')->nullable()->after('user_agent');
            }

            if (!Schema::hasColumn('offer_conversions', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('raw');
            }
        });
    }

    public function down(): void
    {
        // Down minimal volontaire
    }
};
