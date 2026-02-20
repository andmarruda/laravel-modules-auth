<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->unsignedBigInteger('inviter_id');
            $table->string('inviter_type');
            $table->string('role')->default('member');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'email']);
            $table->index(['email', 'accepted_at']);
            $table->index(['inviter_type', 'inviter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_invitations');
    }
};
