<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('round')->default(1);
            $table->unsignedInteger('position');
            // A team, not an individual participant, is the combatant here —
            // Beginner/Upper Beginner only determines who partners with whom
            // (see tournament_teams); the knockout bracket is team vs team.
            $table->foreignId('team_one_id')->nullable()->constrained('tournament_teams')->nullOnDelete();
            $table->foreignId('team_two_id')->nullable()->constrained('tournament_teams')->nullOnDelete();
            $table->timestamps();

            $table->index(['round', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
    }
};
