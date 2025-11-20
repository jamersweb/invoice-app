<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->string('customer'); // Customer name
            $table->date('date_of_transaction');
            $table->decimal('net_amount', 18, 2);
            $table->decimal('profit_margin', 5, 2); // Percentage
            $table->decimal('disbursement_charges', 18, 2)->default(0);
            $table->integer('sales_cycle'); // Days
            $table->string('status')->default('Ongoing'); // Ongoing, Ended
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer', 'status']);
            $table->index(['date_of_transaction', 'status']);
            $table->index('transaction_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

