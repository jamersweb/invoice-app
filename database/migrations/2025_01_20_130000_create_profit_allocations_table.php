<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profit_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->string('investor_name');
            $table->decimal('individual_profit', 18, 2);
            $table->decimal('profit_percentage', 5, 2); // Percentage of total profit
            $table->string('deal_status')->default('Ongoing'); // Ongoing, Ended
            $table->date('allocation_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->index(['investor_name', 'deal_status']);
            $table->index(['transaction_id', 'deal_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_allocations');
    }
};

