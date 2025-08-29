<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('position', ['top', 'middle', 'bottom', 'sidebar']);
            $table->enum('media_type', ['image', 'video']);
            $table->string('media_url');
            $table->string('link_url')->nullable();
            $table->integer('priority')->default(1);
            $table->enum('status', \App\Enums\BannerStatus::values())->default('active');
            $table->timestamp('start_date')->useCurrent();
            $table->timestamp('end_date')->nullable();
            $table->integer('click_count')->default(0);
            $table->integer('impression_count')->default(0);
            $table->decimal('revenue_earned', 10, 2)->default(0);
            $table->foreignId('created_by')->constrained('user_profiles')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['status', 'position']);
            $table->index(['start_date', 'end_date']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
