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
        Schema::table('agreements', function (Blueprint $table) {
            $table->unsignedBigInteger('agreement_template_id')->nullable()->change();
            $table->foreignId('supplier_id')->after('invoice_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('document_type')->after('supplier_id')->nullable();
            $table->string('file_name')->after('document_type')->nullable();
            $table->string('category')->after('file_name')->default('contract');
            $table->text('notes')->after('category')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'document_type', 'file_name', 'category', 'notes']);
            $table->unsignedBigInteger('agreement_template_id')->nullable(false)->change();
        });
    }
};
