<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('invoice_prefix', 20)->default('INV');
            $table->unsignedInteger('invoice_start_number')->default(1);
            $table->string('currency', 10)->default('INR');
            $table->string('currency_symbol', 5)->default('₹');
            $table->string('timezone', 50)->default('Asia/Kolkata');
            $table->string('date_format', 20)->default('d/m/Y');
            $table->string('tax_name', 30)->nullable();         // e.g. GST, VAT
            $table->decimal('default_tax_rate', 5, 2)->nullable();
            $table->boolean('show_tax_on_invoice')->default(true);
            $table->text('invoice_footer_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
