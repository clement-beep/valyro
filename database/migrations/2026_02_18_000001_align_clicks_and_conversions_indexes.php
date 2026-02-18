<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /**
         * ------------------------------------------------------------
         * offer_clicks : ajouter meta (car OffersController l’utilise)
         * ------------------------------------------------------------
         */
        Schema::table('offer_clicks', function (Blueprint $table) {
            if (!Schema::hasColumn('offer_clicks', 'meta')) {
                $table->json('meta')->nullable()->after('risk_flags');
            }
        });

        /**
         * ------------------------------------------------------------
         * offer_conversions : enlever UNIQUE subid si existe + index simple
         * ------------------------------------------------------------
         * On fait ça en SQL conditionnel via information_schema,
         * car Laravel n’a pas "hasIndex" fiable sans requête.
         */
        $db = DB::getDatabaseName();

        // Drop unique on subid if it exists
        $subidUnique = DB::selectOne("
            SELECT INDEX_NAME AS name
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = 'offer_conversions'
              AND NON_UNIQUE = 0
              AND COLUMN_NAME = 'subid'
            LIMIT 1
        ", [$db]);

        if ($subidUnique && !empty($subidUnique->name)) {
            DB::statement("ALTER TABLE offer_conversions DROP INDEX `{$subidUnique->name}`");
        }

        // Ensure non-unique index on subid
        $subidIndex = DB::selectOne("
            SELECT INDEX_NAME AS name
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = 'offer_conversions'
              AND COLUMN_NAME = 'subid'
              AND NON_UNIQUE = 1
            LIMIT 1
        ", [$db]);

        if (!$subidIndex) {
            DB::statement("ALTER TABLE offer_conversions ADD INDEX offer_conversions_subid_index (subid)");
        }

        /**
         * ------------------------------------------------------------
         * offer_conversions : enlever UNIQUE offer_click_id si existe + index simple
         * ------------------------------------------------------------
         */
        $offerClickUnique = DB::selectOne("
            SELECT INDEX_NAME AS name
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = 'offer_conversions'
              AND NON_UNIQUE = 0
              AND COLUMN_NAME = 'offer_click_id'
            LIMIT 1
        ", [$db]);

        if ($offerClickUnique && !empty($offerClickUnique->name)) {
            // Attention: si FK dépend, il faut drop FK puis drop index puis remettre FK.
            // On le fait proprement.

            // Trouver le nom de la FK
            $fk = DB::selectOne("
                SELECT CONSTRAINT_NAME AS name
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ?
                  AND TABLE_NAME = 'offer_conversions'
                  AND COLUMN_NAME = 'offer_click_id'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            ", [$db]);

            if ($fk && !empty($fk->name)) {
                DB::statement("ALTER TABLE offer_conversions DROP FOREIGN KEY `{$fk->name}`");
            }

            DB::statement("ALTER TABLE offer_conversions DROP INDEX `{$offerClickUnique->name}`");

            // Ajouter index normal
            $offerClickIndex = DB::selectOne("
                SELECT INDEX_NAME AS name
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = ?
                  AND TABLE_NAME = 'offer_conversions'
                  AND COLUMN_NAME = 'offer_click_id'
                  AND NON_UNIQUE = 1
                LIMIT 1
            ", [$db]);

            if (!$offerClickIndex) {
                DB::statement("ALTER TABLE offer_conversions ADD INDEX offer_conversions_offer_click_id_index (offer_click_id)");
            }

            // Remettre FK (nom propre)
            DB::statement("
                ALTER TABLE offer_conversions
                ADD CONSTRAINT offer_conversions_offer_click_id_foreign
                FOREIGN KEY (offer_click_id) REFERENCES offer_clicks(id)
                ON DELETE CASCADE
            ");
        } else {
            // Même si unique n’existe plus (comme chez toi), on s’assure que l’index simple existe
            $offerClickIndex = DB::selectOne("
                SELECT INDEX_NAME AS name
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = ?
                  AND TABLE_NAME = 'offer_conversions'
                  AND COLUMN_NAME = 'offer_click_id'
                  AND NON_UNIQUE = 1
                LIMIT 1
            ", [$db]);

            if (!$offerClickIndex) {
                DB::statement("ALTER TABLE offer_conversions ADD INDEX offer_conversions_offer_click_id_index (offer_click_id)");
            }
        }
    }

    public function down(): void
    {
        // Down volontairement minimal (safe)
    }
};
