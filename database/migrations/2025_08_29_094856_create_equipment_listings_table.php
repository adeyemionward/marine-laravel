<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\EquipmentCondition;
use App\Enums\ListingStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('user_profiles')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('equipment_categories')->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->year('year')->nullable();
            $table->enum('condition', EquipmentCondition::values());
            $table->decimal('price', 15, 2)->nullable();
            $table->string('currency', 3)->default('NGN');
            $table->boolean('is_price_negotiable')->default(false);
            $table->boolean('is_poa')->default(false);
            $table->json('specifications')->nullable();
            $table->json('features')->nullable();
            $table->string('location_state')->nullable();
            $table->string('location_city')->nullable();
            $table->text('location_address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('hide_address')->default(false);
            $table->boolean('delivery_available')->default(false);
            $table->integer('delivery_radius')->nullable();
            $table->decimal('delivery_fee', 10, 2)->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_whatsapp')->nullable();
            $table->json('contact_methods')->nullable();
            $table->json('availability_hours')->nullable();
            $table->boolean('allows_inspection')->default(true);
            $table->boolean('allows_test_drive')->default(false);
            $table->enum('status', ListingStatus::values())->default(ListingStatus::DRAFT->value);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->integer('view_count')->default(0);
            $table->integer('inquiry_count')->default(0);
            $table->json('images')->nullable();
            $table->json('tags')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index(['seller_id', 'status']);
            $table->index(['category_id', 'status']);
            $table->index(['status', 'published_at']);
            $table->index(['is_featured', 'status']);
            $table->index(['location_state', 'location_city']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_listings');
    }
};
