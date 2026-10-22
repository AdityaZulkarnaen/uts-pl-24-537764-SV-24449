<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('LAB-####'),
            'name' => fake()->randomElement([
                'Mikroskop Binokuler', 'Multimeter Digital', 'Osiloskop', 'Solder Station',
                'Power Supply DC', 'Arduino Uno', 'Raspberry Pi 4', 'Proyektor', 'Router Wi-Fi',
            ]),
            'category' => fake()->randomElement(['Elektronika', 'Jaringan', 'Komputer', 'Optik', 'Multimedia']),
            'quantity' => fake()->numberBetween(1, 30),
            'condition' => fake()->randomElement(Item::CONDITIONS),
            'location' => 'Lab '.fake()->randomElement(['A', 'B', 'C']).'-'.fake()->numberBetween(101, 305),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
