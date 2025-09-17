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
        Schema::table('banners', function (Blueprint $table) {
            $table->foreignId('purchaser_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->enum('purchase_status', ['pending_payment', 'paid', 'expired', 'cancelled'])->default('pending_payment');
            $table->timestamp('purchased_at')->nullable();
            $table->json('pricing_details')->nullable(); // Store pricing breakdown
            $table->string('payment_reference')->nullable();
            $table->enum('banner_type', ['header', 'hero', 'sidebar', 'footer'])->default('header');
            $table->integer('duration_days')->default(30);
            $table->boolean('auto_approve')->default(false);
            $table->text('admin_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropForeign(['purchaser_id']);
            $table->dropColumn([
                'purchaser_id',
                'purchase_price',
                'purchase_status',
                'purchased_at',
                'pricing_details',
                'payment_reference',
                'banner_type',
                'duration_days',
                'auto_approve',
                'admin_notes'
            ]);
        });
    }
};
