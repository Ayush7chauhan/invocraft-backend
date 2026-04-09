<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);          // e.g. Piece, Kilogram, Litre
            $table->string('short_name', 20);     // e.g. pcs, kg, ltr
            $table->timestamps();

            // A user cannot have two units with the same name
            $table->unique(['user_id', 'name']);

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
