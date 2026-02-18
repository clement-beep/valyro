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
                $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
            }

            if (!Schema::hasColumn('transactions', 'type')) {
                $table->string('type', 30)->after('user_id'); // earn, withdraw, adjust...
            }

            if (!Schema::hasColumn('transactions', 'amount_cents')) {
                $table->unsignedBigInteger('amount_cents')->default(0)->after('type');
            }

            if (!Schema::hasColumn('transactions', 'currency')) {
                $table->string('currency', 3)->default('EUR')->after('amount_cents');
            }

            if (!Schema::hasColumn('transactions', 'status')) {
                $table->string('status', 30)->default('confirmed')->after('currency'); // pending/confirmed/rejected
            }

            if (!Schema::hasColumn('transactions', 'source')) {
                $table->string('source', 50)->nullable()->after('status'); // cpx/manual/...
            }

            if (!Schema::hasColumn('transactions', 'reference')) {
                $table->string('reference', 100)->nullable()->after('source');
            }

            if (!Schema::hasColumn('transactions', 'note')) {
                $table->string('note', 255)->nullable()->after('reference');
            }

            if (!Schema::hasColumn('transactions', 'balance_before_cents')) {
                $table->unsignedBigInteger('balance_before_cents')->default(0)->after('note');
            }

            if (!Schema::hasColumn('transactions', 'balance_after_cents')) {
                $table->unsignedBigInteger('balance_after_cents')->default(0)->after('balance_before_cents');
            }

            if (!Schema::hasColumn('transactions', 'occurred_at')) {
                $table->timestamp('occurred_at')->nullable()->after('balance_after_cents');
            }

            // index utile
            $table->index(['user_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // On évite de dropper en down si tu veux garder l’historique,
            // mais tu peux le faire si tu veux. (On laisse vide volontairement.)
        });
    }
};
