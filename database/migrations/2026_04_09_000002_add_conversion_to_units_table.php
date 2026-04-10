<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Formula stored:  1 [this unit] = [conversion_factor] [base_unit]
     *
     * Examples:
     *   Gram      → base_unit="kg",  conversion_factor=0.001   (1 g = 0.001 kg)
     *   Quintal   → base_unit="kg",  conversion_factor=100     (1 q = 100 kg)
     *   Dozen     → base_unit="pcs", conversion_factor=12      (1 doz = 12 pcs)
     */
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            // Short name of the base/reference unit (e.g. "kg", "l", "pcs", "m")
            $table->string('base_unit', 20)->nullable()->after('type');

            // 1 [this unit] = conversion_factor [base_unit]
            $table->decimal('conversion_factor', 15, 6)->nullable()->after('base_unit');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['base_unit', 'conversion_factor']);
        });
    }
};
