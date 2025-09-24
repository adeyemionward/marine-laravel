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
            $table->string('driver')->default('smtp'); // smtp, gmail, outlook, custom
            $table->string('host')->nullable();
            $table->integer('port')->default(587);
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('encryption')->default('tls'); // tls, ssl, null
            $table->string('from_email');
            $table->string('from_name');
            $table->boolean('is_active')->default(false);
            $table->boolean('use_tls')->default(true);
            $table->json('configuration')->nullable();
            $table->timestamp('tested_at')->nullable();
            $table->text('test_result')->nullable();
            $table->timestamps();
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
