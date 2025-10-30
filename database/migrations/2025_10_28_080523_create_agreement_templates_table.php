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
        Schema::create('agreement_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('version');
            $table->longText('html');
            $table->timestamps();
            $table->unique(['name','version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agreement_templates');
    }
};
