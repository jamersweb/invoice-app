<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('requested_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('document_type_id');
            $table->string('note')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['supplier_id','document_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requested_documents');
    }
};


