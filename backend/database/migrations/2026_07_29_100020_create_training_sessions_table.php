<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->unique()->constrained('training_schedules')->cascadeOnDelete();
            $table->text('materials')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('documented_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
