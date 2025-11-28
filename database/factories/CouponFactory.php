<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'CPN' . Str::upper(Str::random(3)) . $this->faker->unique()->numberBetween(1000, 9999),
            'value' => $this->faker->numberBetween(10, 100),
            'is_used' => false,
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the coupon is used.
     */
    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_used' => true,
        ]);
    }

    /**
     * Indicate that the coupon has high value.
     */
    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => $this->faker->numberBetween(50, 100),
        ]);
    }

    /**
     * Indicate that the coupon has low value.
     */
    public function lowValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => $this->faker->numberBetween(10, 49),
        ]);
    }
}