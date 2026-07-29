<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained('coaches')->nullOnDelete();
            $table->foreignId('court_id')->nullable()->constrained('courts')->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('capacity_min')->default(1);
            $table->unsignedInteger('capacity_max')->default(8);
            $table->unsignedInteger('session_duration')->default(60);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
