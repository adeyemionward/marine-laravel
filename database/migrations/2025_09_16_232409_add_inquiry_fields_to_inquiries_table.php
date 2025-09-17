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
        Schema::table('inquiries', function (Blueprint $table) {
            $table->unsignedBigInteger('listing_id')->nullable()->after('id');
            $table->unsignedBigInteger('inquirer_id')->nullable()->after('listing_id');
            $table->string('inquirer_name')->after('inquirer_id');
            $table->string('inquirer_email')->after('inquirer_name');
            $table->string('inquirer_phone')->nullable()->after('inquirer_email');
            $table->string('subject')->after('inquirer_phone');
            $table->text('message')->after('subject');
            $table->string('budget_range')->nullable()->after('message');
            $table->enum('status', ['pending', 'responded', 'closed'])->default('pending')->after('budget_range');
            $table->timestamp('responded_at')->nullable()->after('status');

            // Add foreign key constraints
            $table->foreign('listing_id')->references('id')->on('equipment_listings')->onDelete('cascade');
            $table->foreign('inquirer_id')->references('id')->on('user_profiles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropForeign(['listing_id']);
            $table->dropForeign(['inquirer_id']);
            $table->dropColumn([
                'listing_id',
                'inquirer_id',
                'inquirer_name',
                'inquirer_email',
                'inquirer_phone',
                'subject',
                'message',
                'budget_range',
                'status',
                'responded_at'
            ]);
        });
    }
};
