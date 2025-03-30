<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('artwork_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('color_variant_id')->nullable();
            $table->unsignedBigInteger('size_variant_id')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->timestamps();

            $table->foreign('color_variant_id')->references('id')->on('artwork_color_variants')->onDelete('cascade');
            $table->foreign('size_variant_id')->references('id')->on('artwork_size_variants')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
