<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_no' => 'INV-TEST-'.Str::upper(Str::random(8)),
            'participant_id' => Participant::factory(),
            'branch_id' => Branch::factory(),
            'due_date' => now()->addDays(7),
            'status' => Invoice::STATUS_UNPAID,
            'total_amount' => 100000,
            'issued_by' => User::factory(),
        ];
    }
}
