<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'article_coins_balance')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unsignedInteger('article_coins_balance')->default(2)->after('is_manager');
            });
        }

        DB::table('users')
            ->whereNull('article_coins_balance')
            ->orWhere('article_coins_balance', '<', 0)
            ->update(['article_coins_balance' => 2]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'article_coins_balance')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('article_coins_balance');
        });
    }
};
