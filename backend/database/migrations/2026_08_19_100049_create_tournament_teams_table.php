<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_teams', function (Blueprint $table) {
            $table->id();
            // A team is one Beginner partnered with one Upper Beginner. Either
            // side can end up null-partnered-with-null-never (both are always
            // set on creation) but a side can point at a same-category
            // leftover, or be the only member (solo, no partner available),
            // when sign-ups are uneven between the two categories.
            $table->foreignId('participant_one_id')->nullable()->constrained('tournament_participants')->nullOnDelete();
            $table->foreignId('participant_two_id')->nullable()->constrained('tournament_participants')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_teams');
    }
};
