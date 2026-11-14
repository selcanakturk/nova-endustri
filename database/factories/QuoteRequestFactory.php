<?php

namespace Database\Factories;

use App\Models\QuoteRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QuoteRequest> */
class QuoteRequestFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(), 'email' => fake()->safeEmail(), 'phone' => '+90 555 123 45 67',
            'company' => fake()->company(), 'status' => 'new', 'admin_note' => null,
            'product_id' => null, 'product_name_snapshot' => fake()->words(3, true), 'quantity' => '5.000', 'unit' => 'adet', 'message' => fake()->paragraph(), 'quoted_at' => null,
        ];
    }
}
