<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('training_sessions')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('method', 20)->default('manual');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'participant_id']);
            $table->index(['participant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
