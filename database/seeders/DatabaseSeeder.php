<?php

namespace Database\Seeders;

use App\Models\Artwork;
use App\Models\Category;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'type' => 'customer',
            'password' =>Hash::make('password')
        ]);

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'test@example.com',
            'type' => 'admin',
            'password' =>Hash::make('password')
        ]);


        // Create users
        $users = User::factory(10)->create();

        // Create categories
        $categories = Category::factory(5)->create();

        // Create artworks with their variants and images
        $artworks = Artwork::factory(20)->create();

        // Create carts for some users
        $users->each(function ($user) use ($artworks) {
            // Each user adds 1-3 random artworks to their cart
            $randomArtworks = $artworks->random(rand(1, 3));

            $randomArtworks->each(function ($artwork) use ($user) {
                // Get a random size and color variant for the artwork
                $sizeVariant = $artwork->sizeVariants()->inRandomOrder()->first();
                $colorVariant = $artwork->colorVariants()->inRandomOrder()->first();

                $user->cart()->create([
                    'artwork_id' => $artwork->id,
                    'quantity' => rand(1, 3),
                    'color_variant_id' => $colorVariant->id,
                    'size_variant_id' => $sizeVariant->id,
                ]);
            });
        });
    }
}
