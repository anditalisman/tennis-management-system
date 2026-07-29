<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participant_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('participants')->restrictOnDelete();
            $table->foreignId('package_id')->constrained('packages')->restrictOnDelete();
            $table->unsignedInteger('sessions_remaining');
            $table->date('valid_until')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('purchased_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['participant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_packages');
    }
};
