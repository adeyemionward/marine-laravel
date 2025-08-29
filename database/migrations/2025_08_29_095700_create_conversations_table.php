<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('equipment_listings')->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained('user_profiles')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('user_profiles')->onDelete('cascade');
            $table->string('subject')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            
            $table->index(['listing_id', 'is_active']);
            $table->index(['buyer_id', 'is_active']);
            $table->index(['seller_id', 'is_active']);
            $table->index('last_message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
