<?php

namespace Database\Factories;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => Str::upper(Str::random(8)),
            'discount_type' => Voucher::TYPE_FIXED,
            'discount_value' => 50000,
            'status' => Voucher::STATUS_ACTIVE,
        ];
    }
}
