<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Discount applied to invoice total (before tax if needed)
            $table->decimal('discount', 12, 2)->default(0)->after('subtotal');

            // Add soft deletes for invoices
            $table->softDeletes()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('discount');
            $table->dropSoftDeletes();
        });
    }
};
