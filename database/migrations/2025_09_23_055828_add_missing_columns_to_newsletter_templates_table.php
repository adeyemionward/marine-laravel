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
        Schema::table('newsletter_templates', function (Blueprint $table) {
            // Add new columns that are missing
            $table->string('name')->nullable()->after('id');
            $table->text('html_content')->nullable()->after('html_template');
            $table->string('thumbnail')->nullable()->after('html_content');
            $table->enum('category', ['basic', 'promotional', 'newsletter', 'announcement'])->default('basic')->after('thumbnail');
            $table->boolean('is_active')->default(true)->after('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('newsletter_templates', function (Blueprint $table) {
            $table->dropColumn(['name', 'html_content', 'thumbnail', 'category', 'is_active']);
        });
    }
};
