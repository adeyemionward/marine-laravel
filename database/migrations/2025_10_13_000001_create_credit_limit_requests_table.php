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
        Schema::create('credit_limit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('Customer requesting credit limit change');
            $table->decimal('current_limit', 15, 2)->default(0)->comment('Current credit limit');
            $table->decimal('requested_limit', 15, 2)->comment('Requested credit limit');
            $table->text('reason')->nullable()->comment('Reason for credit limit change');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null')->comment('Admin who reviewed the request');
            $table->text('review_notes')->nullable()->comment('Admin notes on the review');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('reviewed_by');
        });

        // Create table for credit limit history/audit trail
        Schema::create('credit_limit_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('old_limit', 15, 2)->comment('Previous credit limit');
            $table->decimal('new_limit', 15, 2)->comment('New credit limit');
            $table->enum('change_type', ['manual', 'request_approved', 'automatic'])->comment('How the change was made');
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null')->comment('Admin who made the change');
            $table->text('reason')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('change_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_limit_history');
        Schema::dropIfExists('credit_limit_requests');
    }
};
