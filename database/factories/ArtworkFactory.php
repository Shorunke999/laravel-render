<?php

namespace Database\Factories;

use App\Models\Artwork;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Artwork>
 */
class ArtworkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true),
            'category_id' => Category::factory(),
            'base_price' => $this->faker->randomFloat(2, 10, 500),
            'description' => $this->faker->optional()->paragraph(),
            'stock' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Artwork $artwork) {
            // Create size variants
            $artwork->sizeVariants()->createMany([
                [
                    'size' => 'Small',
                    'price_increment' => $this->faker->randomFloat(2, 0, 20),
                    'stock' => $this->faker->numberBetween(0, 50)
                ],
                [
                    'size' => 'Medium',
                    'price_increment' => $this->faker->randomFloat(2, 0, 30),
                    'stock' => $this->faker->numberBetween(0, 50)
                ],
                [
                    'size' => 'Large',
                    'price_increment' => $this->faker->randomFloat(2, 0, 50),
                    'stock' => $this->faker->numberBetween(0, 50)
                ]
            ]);

            // Create color variants
            $artwork->colorVariants()->createMany([
                [
                    'color' => 'Red',
                    'price_increment' => $this->faker->randomFloat(2, 0, 20),
                    'stock' => $this->faker->numberBetween(0, 50)
                ],
                [
                    'color' => 'Blue',
                    'price_increment' => $this->faker->randomFloat(2, 0, 20),
                    'stock' => $this->faker->numberBetween(0, 50)
                ],
                [
                    'color' => 'Green',
                    'price_increment' => $this->faker->randomFloat(2, 0, 20),
                    'stock' => $this->faker->numberBetween(0, 50)
                ]
            ]);

            // Create artwork images
            $artwork->images()->createMany(
                collect(range(1, $this->faker->numberBetween(1, 3)))
                    ->map(fn () => ['image_url' => $this->faker->imageUrl()])
                    ->toArray()
            );
        });
    }
}
