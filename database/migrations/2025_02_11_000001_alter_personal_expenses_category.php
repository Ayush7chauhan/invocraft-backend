<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Allow all frontend categories: change enum to string
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE personal_expenses MODIFY category VARCHAR(50) NOT NULL DEFAULT 'other'");
        } else {
            Schema::table('personal_expenses', function (Blueprint $table) {
                $table->string('category', 50)->default('other')->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE personal_expenses MODIFY category ENUM('food','travel','entertainment','shopping','bills','health','education','transport','gifts','other') NOT NULL DEFAULT 'other'");
        }
    }
};
