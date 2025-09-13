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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('payment_reference')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('payment_notes')->nullable();
            $table->string('payment_proof_public_id')->nullable(); // Cloudinary public_id
            $table->string('payment_proof_url')->nullable(); // Cloudinary URL
            $table->timestamp('payment_submitted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'payment_reference',
                'payment_method',
                'payment_notes',
                'payment_proof_public_id',
                'payment_proof_url',
                'payment_submitted_at'
            ]);
        });
    }
};