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
            if (!Schema::hasColumn('newsletters', 'excerpt')) {
                $table->text('excerpt')->nullable();
            }
            if (!Schema::hasColumn('newsletters', 'status')) {
                $table->enum('status', ['draft', 'scheduled', 'sent'])->default('draft');
            }
            if (!Schema::hasColumn('newsletters', 'scheduled_at')) {
                $table->datetime('scheduled_at')->nullable();
            }
            if (!Schema::hasColumn('newsletters', 'sent_at')) {
                $table->datetime('sent_at')->nullable();
            }
            if (!Schema::hasColumn('newsletters', 'recipient_count')) {
                $table->integer('recipient_count')->default(0);
            }
            if (!Schema::hasColumn('newsletters', 'open_count')) {
                $table->integer('open_count')->default(0);
            }
            if (!Schema::hasColumn('newsletters', 'click_count')) {
                $table->integer('click_count')->default(0);
            }
            if (!Schema::hasColumn('newsletters', 'tags')) {
                $table->json('tags')->nullable();
            }
            if (!Schema::hasColumn('newsletters', 'template_id')) {
                $table->foreignId('template_id')->nullable()->constrained('newsletter_templates')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('newsletters', function (Blueprint $table) {
            //
        });
    }
};
