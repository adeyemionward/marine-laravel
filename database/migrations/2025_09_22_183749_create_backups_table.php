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
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
             $table->string('type')->default('full'); // full/incremental (if needed later)
            $table->string('status')->default('pending'); // pending, in_progress, completed, failed
            $table->string('file_path')->nullable();
            $table->float('size')->nullable(); // in MB
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
             $table->float('duration')->nullable(); // seconds
            $table->string('created_by')->default('System Administrator');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
