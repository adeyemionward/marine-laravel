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
        Schema::create('equipment_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_listing_id')->constrained('equipment_listings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('rating')->unsigned()->comment('1-5 star rating');
            $table->string('title')->nullable();
            $table->text('comment');
            $table->json('images')->nullable()->comment('Review photos');
            $table->boolean('is_verified_purchase')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->text('seller_reply')->nullable();
            $table->timestamp('seller_replied_at')->nullable();
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('equipment_listing_id');
            $table->index('user_id');
            $table->index('rating');
            $table->index('status');

            // Unique constraint: one review per user per listing
            $table->unique(['equipment_listing_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_reviews');
    }
};
