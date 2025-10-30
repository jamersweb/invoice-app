<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('legal_name')->nullable();
            $table->string('tax_registration_number')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('business_type')->nullable(); // LLC, Corporation, Partnership, etc.
            $table->string('industry')->nullable();
            $table->date('incorporation_date')->nullable();
            $table->string('country')->nullable();
            $table->string('state_province')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('website')->nullable();
            $table->string('kyb_status')->default('pending'); // pending, under_review, approved, rejected
            $table->string('grade')->nullable(); // A/B/C/D
            $table->text('kyb_notes')->nullable();
            $table->timestamp('kyb_approved_at')->nullable();
            $table->unsignedBigInteger('kyb_approved_by')->nullable();
            $table->json('kyc_data')->nullable(); // Store additional KYC information
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index('kyb_status');
            $table->index('contact_email');
            $table->foreign('kyb_approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};


