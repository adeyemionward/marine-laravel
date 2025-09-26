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
        Schema::create('email_settings', function (Blueprint $table) {
            $table->id();
            $table->string('driver')->default('smtp'); // smtp, ses, mailgun, etc.
            $table->string('host')->nullable();
            $table->integer('port')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('encryption')->nullable(); // tls, ssl, null
            $table->string('from_name')->default('Marine.ng');
            $table->string('from_email')->default('noreply@marine.ng');
            $table->boolean('is_active')->default(false);
            $table->json('additional_config')->nullable(); // for API keys, etc.
            $table->timestamp('last_tested_at')->nullable();
            $table->boolean('test_passed')->default(false);
            $table->text('test_error')->nullable();
            $table->timestamps();

            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_settings');
    }
};
