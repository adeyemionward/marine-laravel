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
        Schema::create('banner_purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('company_name');
            $table->string('contact_email');
            $table->string('contact_phone');

            // Banner details
            $table->enum('banner_position', ['top', 'middle', 'bottom', 'left', 'right', 'hero']);
            $table->enum('banner_duration', ['1_week', '2_weeks', '1_month', '3_months', '6_months', '1_year']);
            $table->text('target_pages')->nullable(); // JSON array of pages
            $table->text('banner_description');
            $table->string('target_url')->nullable();

            // Pricing
            $table->decimal('price', 10, 2);
            $table->enum('payment_status', ['pending', 'invoiced', 'paid', 'confirmed', 'cancelled'])->default('pending');

            // Invoice details
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->string('invoice_number')->nullable();
            $table->timestamp('invoice_sent_at')->nullable();
            $table->timestamp('payment_received_at')->nullable();
            $table->timestamp('payment_confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('set null');

            // Banner creation details
            $table->foreignId('banner_id')->nullable()->constrained('banners')->onDelete('set null');
            $table->enum('status', ['pending', 'approved', 'rejected', 'in_progress', 'completed'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // File uploads
            $table->string('banner_image_url')->nullable();
            $table->string('company_logo_url')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('payment_status');
            $table->index('invoice_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banner_purchase_requests');
    }
};