<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Investor name/entity
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('AED');
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['name', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};

