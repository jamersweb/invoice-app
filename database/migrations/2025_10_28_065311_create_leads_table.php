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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('company_email');
            $table->string('company_phone')->nullable();
            $table->string('company_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('status')->default('new'); // new, verified, converted
            $table->string('verify_token')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['company_email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
