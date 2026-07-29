<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['class_id', 'participant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_members');
    }
};
