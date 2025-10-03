<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('seller_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained('equipment_listings')->nullOnDelete();
            $table->integer('rating'); // 1-5 stars
            $table->text('review')->nullable();
            $table->json('review_categories')->nullable(); // communication, quality, delivery, etc.
            $table->boolean('is_verified_purchase')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['seller_id', 'reviewer_id', 'listing_id']);
            $table->index(['seller_id', 'rating']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('seller_reviews');
    }
};