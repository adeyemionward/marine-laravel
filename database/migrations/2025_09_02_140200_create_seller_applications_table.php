<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('seller_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('business_name');
            $table->text('business_description');
            $table->string('business_registration_number')->nullable();
            $table->string('tax_identification_number')->nullable();
            $table->json('business_documents'); // Array of document paths
            $table->json('specialties'); // Array of specialties
            $table->integer('years_experience');
            $table->string('previous_platforms')->nullable();
            $table->text('motivation')->nullable(); // Why they want to be verified
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('seller_applications');
    }
};