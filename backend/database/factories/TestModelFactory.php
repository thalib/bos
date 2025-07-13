<?php

namespace Database\Factories;

use App\Models\TestModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TestModel>
 */
class TestModelFactory extends Factory
{
    protected $model = TestModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'active' => fake()->boolean(80), // 80% chance of being true
        ];
    }
}
