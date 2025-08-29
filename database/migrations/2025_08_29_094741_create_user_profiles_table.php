<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\UserRole;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_profiles')) {
            Schema::create('user_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('full_name');
                $table->enum('role', UserRole::values())->default(UserRole::USER->value);
                $table->boolean('is_active')->default(true);
                $table->string('company_name')->nullable();
                $table->text('company_description')->nullable();
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('country')->default('Nigeria');
                $table->string('avatar')->nullable();
                $table->json('verification_documents')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->timestamp('email_verified_at')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'role']);
                $table->index('is_active');
                $table->index('is_verified');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
