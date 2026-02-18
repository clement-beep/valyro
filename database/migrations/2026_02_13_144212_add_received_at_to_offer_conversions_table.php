<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('offer_conversions', function (Blueprint $table) {

            // 1) raw (souvent absent si table créée depuis postback_events)
            if (!Schema::hasColumn('offer_conversions', 'raw')) {
                $table->json('raw')->nullable();
            }

            // 2) user_agent (utile pour admin / audit)
            if (!Schema::hasColumn('offer_conversions', 'user_agent')) {
                $table->text('user_agent')->nullable();
            }

            // 3) received_at (pour tri admin)
            if (!Schema::hasColumn('offer_conversions', 'received_at')) {
                // pas de after() pour éviter les erreurs si colonnes différentes
                $table->timestamp('received_at')->nullable()->index();
            }
        });

        // Remplir received_at si null (fallback)
        if (Schema::hasColumn('offer_conversions', 'received_at') && Schema::hasColumn('offer_conversions', 'created_at')) {
            DB::statement("UPDATE offer_conversions SET received_at = created_at WHERE received_at IS NULL");
        }
    }

    public function down(): void
    {
        Schema::table('offer_conversions', function (Blueprint $table) {
            if (Schema::hasColumn('offer_conversions', 'received_at')) {
                $table->dropIndex(['received_at']);
                $table->dropColumn('received_at');
            }

            // Optionnel : si tu veux revenir en arrière complètement
            // if (Schema::hasColumn('offer_conversions', 'user_agent')) $table->dropColumn('user_agent');
            // if (Schema::hasColumn('offer_conversions', 'raw')) $table->dropColumn('raw');
        });
    }
};
