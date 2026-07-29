<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->string('name');
            $table->string('relation', 50);
            $table->string('phone', 30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_contacts');
    }
};
