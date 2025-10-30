<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('buyer_id');
            $table->string('invoice_number');
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3);
            $table->date('due_date');
            $table->string('status')->index();
            $table->json('ocr_data')->nullable();
            $table->unsignedTinyInteger('ocr_confidence')->nullable();
            $table->boolean('is_duplicate_flag')->default(false)->index();
            $table->string('file_path')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['buyer_id', 'invoice_number']);
            $table->index(['supplier_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};


