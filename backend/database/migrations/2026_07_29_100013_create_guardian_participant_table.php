<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardian_participant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guardian_id')->constrained('guardians')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();

            $table->unique(['guardian_id', 'participant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_participant');
    }
};
