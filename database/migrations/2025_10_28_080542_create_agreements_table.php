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
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_template_id')->constrained('agreement_templates');
            $table->string('version');
            $table->unsignedBigInteger('signer_id')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_pdf')->nullable();
            $table->json('terms_snapshot_json')->nullable();
            $table->string('status')->default('issued'); // issued, signed
            $table->timestamps();

            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};
