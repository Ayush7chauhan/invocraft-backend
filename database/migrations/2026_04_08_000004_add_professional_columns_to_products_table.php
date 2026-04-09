<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // SKU and barcode support
            $table->string('sku', 100)->nullable()->after('name');
            $table->string('barcode', 100)->nullable()->after('sku');

            // Proper FK to categories table
            $table->foreignId('category_id')
                  ->nullable()
                  ->after('category')
                  ->constrained('categories')
                  ->onDelete('set null');

            // FK to units table
            $table->foreignId('unit_id')
                  ->nullable()
                  ->after('category_id')
                  ->constrained('units')
                  ->onDelete('set null');

            // Product active/inactive status
            $table->enum('status', ['active', 'inactive'])
                  ->default('active')
                  ->after('tax_rate');

            // Unique SKU per user (partial — enforced at app level if null)
            $table->index(['user_id', 'sku']);
            $table->index(['user_id', 'barcode']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['unit_id']);
            $table->dropIndex(['user_id', 'sku']);
            $table->dropIndex(['user_id', 'barcode']);
            $table->dropIndex(['status']);
            $table->dropColumn(['sku', 'barcode', 'category_id', 'unit_id', 'status']);
        });
    }
};
