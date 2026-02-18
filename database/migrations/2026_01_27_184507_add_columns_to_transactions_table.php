<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {

            if (!Schema::hasColumn('transactions', 'user_id')) {
                $table->foreignId('user_id')
                    ->after('id')
                    ->constrained()
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('transactions', 'type')) {
                $table->string('type', 50)->after('user_id'); // ex: earn, withdraw, adjustment
            }

            if (!Schema::hasColumn('transactions', 'amount_cents')) {
                $table->bigInteger('amount_cents')->after('type'); // peut être négatif
            }

            if (!Schema::hasColumn('transactions', 'currency')) {
                $table->string('currency', 3)->default('EUR')->after('amount_cents');
            }

            if (!Schema::hasColumn('transactions', 'status')) {
                $table->string('status', 30)->default('confirmed')->after('currency'); // pending/confirmed/failed
            }

            if (!Schema::hasColumn('transactions', 'source')) {
                $table->string('source', 50)->nullable()->after('status'); // ex: cpx, manual, admin
            }

            if (!Schema::hasColumn('transactions', 'reference')) {
                $table->string('reference', 100)->nullable()->after('source'); // ex: offer_id/click_id
            }

            if (!Schema::hasColumn('transactions', 'note')) {
                $table->text('note')->nullable()->after('reference');
            }

            if (!Schema::hasColumn('transactions', 'balance_before_cents')) {
                $table->bigInteger('balance_before_cents')->nullable()->after('note');
            }

            if (!Schema::hasColumn('transactions', 'balance_after_cents')) {
                $table->bigInteger('balance_after_cents')->nullable()->after('balance_before_cents');
            }

            if (!Schema::hasColumn('transactions', 'occurred_at')) {
                $table->timestamp('occurred_at')->nullable()->after('balance_after_cents');
            }

            // Index utiles
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {

            // drop indexes (Laravel génère des noms auto, on les retire proprement si possible)
            // (Si ça bloque, on pourra les drop à la main avec le nom exact)
            try { $table->dropIndex(['user_id', 'created_at']); } catch (\Throwable $e) {}
            try { $table->dropIndex(['user_id', 'status']); } catch (\Throwable $e) {}

            $cols = [
                'occurred_at',
                'balance_after_cents',
                'balance_before_cents',
                'note',
                'reference',
                'source',
                'status',
                'currency',
                'amount_cents',
                'type',
            ];

            foreach ($cols as $col) {
                if (Schema::hasColumn('transactions', $col)) {
                    $table->dropColumn($col);
                }
            }

            if (Schema::hasColumn('transactions', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
