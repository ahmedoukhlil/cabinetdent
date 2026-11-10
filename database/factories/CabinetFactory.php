<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Cabinet>
 */
class CabinetFactory extends Factory
{
    public function definition(): array
    {
        return ['nom' => 'Cabinet '.fake()->lastName(), 'actif' => true];
    }
}
