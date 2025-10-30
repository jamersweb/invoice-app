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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->text('account_name');
            $table->text('iban');
            $table->string('swift')->nullable();
            $table->string('bank_letter_path')->nullable();
            $table->timestamps();
            $table->index('supplier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
