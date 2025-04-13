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
            // Create artwork images
            $artwork->images()->createMany(
                collect(range(1, $this->faker->numberBetween(1, 3)))
                    ->map(fn () => ['image_url' => $this->faker->imageUrl()])
                    ->toArray()
            );
        });
    }
}
