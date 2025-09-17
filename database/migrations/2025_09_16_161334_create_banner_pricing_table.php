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
        Schema::create('banner_pricing', function (Blueprint $table) {
            $table->id();
            $table->enum('banner_type', ['header', 'hero', 'sidebar', 'footer']);
            $table->enum('position', ['top', 'middle', 'bottom'])->nullable();
            $table->enum('duration_type', ['daily', 'weekly', 'monthly'])->default('monthly');
            $table->integer('duration_value')->default(1); // e.g., 1 month, 2 weeks
            $table->decimal('base_price', 10, 2);
            $table->decimal('premium_multiplier', 3, 2)->default(1.00); // For premium positions
            $table->json('discount_tiers')->nullable(); // Volume discounts
            $table->boolean('is_active')->default(true);
            $table->integer('max_concurrent')->default(1); // Max banners of this type at once
            $table->text('description')->nullable();
            $table->json('specifications')->nullable(); // Size, format requirements
            $table->timestamps();
            
            $table->unique(['banner_type', 'position', 'duration_type', 'duration_value'], 'banner_pricing_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banner_pricing');
    }
};
