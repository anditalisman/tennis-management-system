<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waiting_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->string('status', 20)->default('waiting');
            $table->timestamps();

            $table->unique(['class_id', 'participant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waiting_lists');
    }
};
