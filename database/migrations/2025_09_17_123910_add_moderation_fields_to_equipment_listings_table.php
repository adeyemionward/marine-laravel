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
        Schema::table('equipment_listings', function (Blueprint $table) {
            // Add moderation fields
            $table->timestamp('moderated_at')->nullable()->after('updated_at');
            $table->unsignedBigInteger('moderated_by')->nullable()->after('moderated_at');
            $table->text('moderation_reason')->nullable()->after('moderated_by');

            // Add foreign key constraint
            $table->foreign('moderated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_listings', function (Blueprint $table) {
            $table->dropForeign(['moderated_by']);
            $table->dropColumn(['moderated_at', 'moderated_by', 'moderation_reason']);
        });
    }
};
