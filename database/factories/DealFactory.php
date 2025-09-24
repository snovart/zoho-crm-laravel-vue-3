<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Deal>
 */
class DealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sources = [
            'Source 1',
            'Source 2',
            'Source 3',
            'Source 4',
            'Source 5'
        ];
        
        return [
            'name'        => 'Deal '.$this->faker->unique()->numberBetween(1, 10000),
            'customer_id' => Customer::inRandomOrder()->value('id') ?? Customer::factory(),
            'source'      => $this->faker->randomElement($sources),
        ];
    }
}
