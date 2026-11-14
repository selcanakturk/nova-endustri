<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContactMessage> */
class ContactMessageFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(), 'email' => fake()->safeEmail(), 'phone' => '+90 555 123 45 67',
            'company' => fake()->company(), 'status' => 'new', 'admin_note' => null,
            'subject' => fake()->sentence(4), 'message' => fake()->paragraph(), 'read_at' => null, 'replied_at' => null,
        ];
    }
}
