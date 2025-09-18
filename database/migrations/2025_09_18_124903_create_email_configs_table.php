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
        Schema::create('email_configs', function (Blueprint $table) {
            $table->id();
            $table->string('driver')->default('custom'); // gmail, outlook, custom
            $table->string('smtp_host');
            $table->integer('smtp_port')->default(587);
            $table->string('username');
            $table->string('password'); // encrypted
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->string('encryption')->default('tls'); // tls, ssl, null
            $table->boolean('enable_smtp')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_configs');
    }
};
