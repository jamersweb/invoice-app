<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount', 18, 2);
            $table->unsignedInteger('tenor_days');
            $table->decimal('discount_rate', 8, 4);
            $table->decimal('discount_amount', 18, 2);
            $table->decimal('admin_fee', 18, 2);
            $table->decimal('net_amount', 18, 2);
            $table->json('pricing_snapshot');
            $table->string('status')->index();
            $table->timestamp('issued_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};


