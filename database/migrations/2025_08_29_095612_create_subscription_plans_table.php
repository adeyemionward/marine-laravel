<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('tier', \App\Enums\SubscriptionTier::values());
            $table->decimal('price', 10, 2)->default(0.00);
            $table->enum('billing_cycle', \App\Enums\BillingCycle::values())->default('monthly');
            $table->json('features')->nullable();
            $table->json('limits')->nullable();
            $table->integer('max_listings')->default(0);
            $table->integer('max_images_per_listing')->default(1);
            $table->boolean('priority_support')->default(false);
            $table->boolean('analytics_access')->default(false);
            $table->boolean('custom_branding')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('user_profiles')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['tier', 'is_active']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
