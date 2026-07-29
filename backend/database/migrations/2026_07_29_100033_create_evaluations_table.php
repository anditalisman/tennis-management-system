<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->foreignId('coach_id')->constrained('coaches')->restrictOnDelete();
            $table->date('evaluation_date');
            $table->text('next_target')->nullable();
            $table->foreignId('recommended_class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->timestamps();

            $table->index(['participant_id', 'evaluation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
