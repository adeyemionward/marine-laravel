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
        Schema::table('newsletters', function (Blueprint $table) {
            $table->longText('content')->nullable()->after('title');
            $table->string('subject')->nullable()->after('content');
            $table->string('from_email')->nullable()->after('subject');
            $table->string('from_name')->nullable()->after('from_email');
            $table->json('recipients')->nullable()->after('from_name');
            $table->json('template_data')->nullable()->after('recipients');
            $table->unsignedBigInteger('created_by')->nullable()->after('template_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('newsletters', function (Blueprint $table) {
            $table->dropColumn(['content', 'subject', 'from_email', 'from_name', 'recipients', 'template_data', 'created_by']);
        });
    }
};
