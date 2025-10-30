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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->constrained('document_types');
            $table->morphs('owner'); // owner_type, owner_id: Supplier/User later
            $table->string('status')->default('pending');
            $table->timestamp('expiry_at')->nullable();
            $table->string('file_path');
            $table->text('review_notes')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedTinyInteger('priority')->default(0);
            $table->boolean('vip')->default(false);
            $table->timestamps();

            $table->index('status');
            $table->index(['assigned_to','priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
