<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Drop UNIQUE sur offer_click_id si elle existe (selon anciens états)
        // Laravel n'a pas hasIndex natif => on check via information_schema
        $db = DB::getDatabaseName();

        $uniqueName = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $db)
            ->where('TABLE_NAME', 'offer_conversions')
            ->where('COLUMN_NAME', 'offer_click_id')
            ->where('NON_UNIQUE', 0)
            ->value('INDEX_NAME');

        if ($uniqueName) {
            Schema::table('offer_conversions', function (Blueprint $table) use ($uniqueName) {
                $table->dropUnique($uniqueName);
            });
        }

        // 2) Ajoute un index simple si pas présent
        $hasIndex = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $db)
            ->where('TABLE_NAME', 'offer_conversions')
            ->where('INDEX_NAME', 'offer_conversions_offer_click_id_index')
            ->exists();

        if (!$hasIndex) {
            Schema::table('offer_conversions', function (Blueprint $table) {
                $table->index('offer_click_id', 'offer_conversions_offer_click_id_index');
            });
        }

        // 3) Subid : doit être index (pas unique)
        $subidUnique = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $db)
            ->where('TABLE_NAME', 'offer_conversions')
            ->where('COLUMN_NAME', 'subid')
            ->where('NON_UNIQUE', 0)
            ->value('INDEX_NAME');

        if ($subidUnique) {
            Schema::table('offer_conversions', function (Blueprint $table) use ($subidUnique) {
                $table->dropUnique($subidUnique);
            });
        }

        $subidIndex = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $db)
            ->where('TABLE_NAME', 'offer_conversions')
            ->where('INDEX_NAME', 'offer_conversions_subid_index')
            ->exists();

        if (!$subidIndex) {
            Schema::table('offer_conversions', function (Blueprint $table) {
                $table->index('subid', 'offer_conversions_subid_index');
            });
        }
    }

    public function down(): void
    {
        // down minimal (on ne remet pas du unique, dangereux en prod)
    }
};
