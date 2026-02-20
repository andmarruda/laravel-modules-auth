<?php

use Andmarruda\AuthModule\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('team_invitations')) {
            return;
        }

        $hasLegacyInvitedBy = Schema::hasColumn('team_invitations', 'invited_by');
        $hasInviterId = Schema::hasColumn('team_invitations', 'inviter_id');
        $hasInviterType = Schema::hasColumn('team_invitations', 'inviter_type');

        if (!$hasInviterId || !$hasInviterType) {
            Schema::table('team_invitations', function (Blueprint $table): void {
                if (!Schema::hasColumn('team_invitations', 'inviter_id')) {
                    $table->unsignedBigInteger('inviter_id')->nullable()->after('token');
                }

                if (!Schema::hasColumn('team_invitations', 'inviter_type')) {
                    $table->string('inviter_type')->nullable()->after('inviter_id');
                }
            });
        }

        if ($hasLegacyInvitedBy) {
            DB::table('team_invitations')
                ->whereNull('inviter_id')
                ->update([
                    'inviter_id' => DB::raw('invited_by'),
                    'inviter_type' => User::class,
                ]);

            Schema::table('team_invitations', function (Blueprint $table): void {
                try {
                    $table->dropForeign(['invited_by']);
                } catch (\Throwable) {
                    // no-op for databases with unknown constraint names
                }
            });

            try {
                Schema::table('team_invitations', function (Blueprint $table): void {
                    $table->dropForeign('team_invitations_invited_by_foreign');
                });
            } catch (\Throwable) {
                // no-op for databases with unknown constraint names
            }

            Schema::withoutForeignKeyConstraints(static function (): void {
                if (!Schema::hasColumn('team_invitations', 'invited_by')) {
                    return;
                }

                Schema::table('team_invitations', function (Blueprint $table): void {
                    $table->dropColumn('invited_by');
                });
            });
        }

        try {
            Schema::table('team_invitations', function (Blueprint $table): void {
                if (!Schema::hasColumn('team_invitations', 'inviter_type') || !Schema::hasColumn('team_invitations', 'inviter_id')) {
                    return;
                }

                $table->index(['inviter_type', 'inviter_id']);
            });
        } catch (\Throwable) {
            // ignore duplicate index and unsupported operations
        }
    }

    public function down(): void
    {
        // Intentionally no-op to avoid destructive rollback on production data.
    }
};
