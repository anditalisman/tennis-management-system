<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('court_id')->constrained('courts')->restrictOnDelete();
            $table->foreignId('coach_id')->constrained('coaches')->restrictOnDelete();
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('type', 20)->default('reguler');
            $table->string('status', 20)->default('scheduled');
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('replaces_schedule_id')->nullable()->constrained('training_schedules')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['court_id', 'session_date']);
            $table->index(['coach_id', 'session_date']);
            $table->index(['class_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_schedules');
    }
};
