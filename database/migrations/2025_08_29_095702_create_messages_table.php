<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('user_profiles')->onDelete('cascade');
            $table->text('content');
            $table->enum('type', ['text', 'offer', 'system', 'attachment'])->default('text');
            $table->enum('status', \App\Enums\MessageStatus::values())->default('sent');
            $table->json('attachments')->nullable();
            $table->decimal('offer_price', 15, 2)->nullable();
            $table->string('offer_currency', 3)->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('read_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
