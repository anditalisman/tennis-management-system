<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('type', 20);
            $table->unsignedInteger('qty');
            $table->foreignId('participant_id')->nullable()->constrained('participants')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['item_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
