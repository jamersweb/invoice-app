<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kyb_checklists', function (Blueprint $table) {
            $table->id();
            $table->string('customer_type')->index(); // e.g., LLC, Sole, Gov, Default
            $table->unsignedBigInteger('document_type_id');
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('expires_in_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['customer_type','document_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyb_checklists');
    }
};


