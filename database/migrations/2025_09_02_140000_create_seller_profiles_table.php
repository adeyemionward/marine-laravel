<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('seller_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('business_name')->nullable();
            $table->text('business_description')->nullable();
            $table->json('specialties')->nullable(); // Array of specialties
            $table->integer('years_active')->default(0);
            $table->decimal('rating', 3, 2)->default(0.00); // e.g., 4.85
            $table->integer('review_count')->default(0);
            $table->integer('total_listings')->default(0);
            $table->string('response_time')->default('24 hours'); // e.g., "< 2 hours"
            $table->integer('avg_response_minutes')->default(1440); // For sorting
            $table->json('verification_documents')->nullable();
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('verification_notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->integer('featured_priority')->default(0);
            $table->json('business_hours')->nullable();
            $table->string('website')->nullable();
            $table->json('social_media')->nullable();
            $table->timestamps();

            $table->index(['verification_status', 'rating']);
            $table->index(['is_featured', 'featured_priority']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('seller_profiles');
    }
};