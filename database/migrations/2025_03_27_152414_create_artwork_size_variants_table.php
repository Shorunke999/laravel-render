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
        Schema::create('artwork_size_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artwork_id')->constrained()->onDelete('cascade');
            $table->string('size');
            $table->decimal('price_increment', 10, 2); // Added price increment
            $table->integer('stock')->default(0);
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artwork_size_variants');
    }
};
