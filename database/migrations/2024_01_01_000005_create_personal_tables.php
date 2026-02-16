<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Personal Expenses Table
        Schema::create('personal_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->enum('category', [
                'food', 'travel', 'entertainment', 'shopping', 
                'bills', 'health', 'education', 'transport', 
                'gifts', 'other'
            ])->default('other');
            $table->date('expense_date');
            $table->enum('payment_method', ['cash', 'upi', 'card', 'bank_transfer', 'other'])->default('cash');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'expense_date']);
            $table->index('category');
        });

        // Personal Purchases Table
        Schema::create('personal_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->enum('category', [
                'electronics', 'clothing', 'groceries', 'furniture',
                'appliances', 'books', 'sports', 'beauty', 'other'
            ])->default('other');
            $table->date('purchase_date');
            $table->enum('payment_method', ['cash', 'upi', 'card', 'bank_transfer', 'other'])->default('cash');
            $table->string('store_name')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'purchase_date']);
            $table->index('category');
        });

        // Personal Contacts (Friends, Family, etc.)
        Schema::create('personal_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('mobile', 15)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->enum('relationship', [
                'friend', 'family', 'colleague', 'neighbor', 'other'
            ])->default('friend');
            $table->decimal('opening_balance', 15, 2)->default(0)->comment('Positive = they owe you, Negative = you owe them');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            
            $table->index(['user_id', 'relationship']);
            $table->index('status');
        });

        // Personal Transactions (Money given/received from friends)
        Schema::create('personal_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('personal_contact_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['given', 'received'])->comment('given = you gave money, received = you received money');
            $table->decimal('amount', 15, 2);
            $table->date('transaction_date');
            $table->text('note')->nullable();
            $table->enum('payment_method', ['cash', 'upi', 'bank_transfer', 'other'])->default('cash');
            $table->string('reference_number')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'personal_contact_id']);
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_transactions');
        Schema::dropIfExists('personal_contacts');
        Schema::dropIfExists('personal_purchases');
        Schema::dropIfExists('personal_expenses');
    }
};


