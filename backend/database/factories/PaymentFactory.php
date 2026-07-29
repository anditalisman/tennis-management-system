<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'method' => Payment::METHOD_TRANSFER,
            'amount' => 100000,
            'status' => Payment::STATUS_PENDING,
            'idempotency_key' => (string) Str::uuid(),
            'submitted_by' => User::factory(),
        ];
    }
}
