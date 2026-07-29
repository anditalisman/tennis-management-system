<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('coaches')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('training_sessions')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['coach_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_attendance');
    }
};
