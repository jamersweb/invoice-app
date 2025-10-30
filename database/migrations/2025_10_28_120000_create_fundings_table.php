<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fundings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('offer_id');
            $table->decimal('amount', 18, 2);
            $table->timestamp('funded_at')->nullable();
            $table->string('bank_reference')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['invoice_id', 'offer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fundings');
    }
};


