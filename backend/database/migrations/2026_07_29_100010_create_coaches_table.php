<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coaches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->json('certifications')->nullable();
            $table->text('bio')->nullable();
            $table->string('employment_status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'employment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coaches');
    }
};
