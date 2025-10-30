<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expected_repayments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('buyer_id');
            $table->decimal('amount', 18, 2);
            $table->date('due_date');
            $table->string('status')->index();
            $table->timestamps();
            $table->index(['invoice_id', 'status']);
        });

        Schema::create('received_repayments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('buyer_id');
            $table->decimal('amount', 18, 2);
            $table->date('received_date');
            $table->string('bank_reference')->nullable();
            $table->decimal('allocated_amount', 18, 2)->default(0);
            $table->decimal('unallocated_amount', 18, 2)->default(0);
            $table->timestamps();
            $table->index(['buyer_id', 'received_date']);
        });

        Schema::create('repayment_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('received_repayment_id');
            $table->unsignedBigInteger('expected_repayment_id');
            $table->decimal('amount', 18, 2);
            $table->timestamps();
            $table->index(['received_repayment_id', 'expected_repayment_id'], 'repay_alloc_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repayment_allocations');
        Schema::dropIfExists('received_repayments');
        Schema::dropIfExists('expected_repayments');
    }
};


