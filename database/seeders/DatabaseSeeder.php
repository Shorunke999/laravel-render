<?php

namespace Database\Seeders;

use App\Models\Artwork;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create predefined users
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example1.com',
            'type' => 'customer',
            'password' => Hash::make('password'),
        ]);

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'test@example2.com',
            'type' => 'admin',
            'password' => Hash::make('password'),
        ]);

        // Generate users, categories, and artworks
        $users = User::factory(10)->create();
        $categories = Category::factory(5)->create();
        $artworks = Artwork::factory(20)->create();

        // Simulate carts
        $users->each(function ($user) use ($artworks) {
            $randomArtworks = $artworks->random(rand(1, 3));

            $randomArtworks->each(function ($artwork) use ($user) {
                $sizeVariant = $artwork->sizeVariants()->inRandomOrder()->first();
                $colorVariant = $artwork->colorVariants()->inRandomOrder()->first();

                if (!$sizeVariant || !$colorVariant) return;

                $user->cart()->create([
                    'artwork_id' => $artwork->id,
                    'quantity' => rand(1, 3),
                    'color_variant_id' => $colorVariant->id,
                    'size_variant_id' => $sizeVariant->id,
                ]);
            });
        });

        // Simulate orders
        $users->each(function ($user) use ($artworks) {
            for ($i = 0; $i < rand(1, 3); $i++) {
                $statusOptions = ['pending', 'processing','delivered'];
                $paymentStatusOptions = ['pending', 'success'];

                $status = $statusOptions[array_rand($statusOptions)];
                $paymentStatus = $status === 'pending' ? 'pending' : 'success';

                $cartArtworks = $artworks->random(rand(1, 3));
                $totalAmount = 0;

                $order = Order::create([
                    'user_id' => $user->id,
                    'total_amount' => 0,
                    'status' => $status,
                    'shipping_address' => json_encode([
                        'street' => fake()->streetAddress(),
                        'city' => fake()->city(),
                        'state' => fake()->state(),
                        'country' => fake()->country(),
                        'zip' => fake()->postcode(),
                    ]),
                    'contact' => fake()->phoneNumber(),
                    'payment_status' => $paymentStatus,
                    'reference_code' => strtoupper(Str::random(10)),
                ]);

                foreach ($cartArtworks as $artwork) {
                    $sizeVariant = $artwork->sizeVariants()->inRandomOrder()->first();
                    $colorVariant = $artwork->colorVariants()->inRandomOrder()->first();

                    if (!$sizeVariant || !$colorVariant) continue;

                    $quantity = rand(1, 3);
                    $price = ($artwork->base_price + $sizeVariant->price_increment + $colorVariant->price_increment) * $quantity;

                    $order->orderItems()->create([
                        'artwork_id' => $artwork->id,
                        'quantity' => $quantity,
                        'price' => $price,
                        'size_variant_id' => $sizeVariant->id,
                        'color_variant_id' => $colorVariant->id,
                    ]);

                    $totalAmount += $price;
                    $artwork->decrement('stock', $quantity);
                }

                $order->update(['total_amount' => $totalAmount]);
            }
        });
    }
}
