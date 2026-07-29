<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->string('method', 20);
            $table->decimal('amount', 14, 2);
            $table->string('status', 20)->default('pending');
            $table->string('reference_no')->nullable();
            $table->string('idempotency_key')->unique();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
