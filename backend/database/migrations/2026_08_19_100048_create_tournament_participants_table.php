<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_participants', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('nickname');
            $table->string('phone', 30);
            $table->string('category', 20);
            $table->timestamps();

            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_participants');
    }
};
