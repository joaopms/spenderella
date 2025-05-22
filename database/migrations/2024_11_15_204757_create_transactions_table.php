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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();

            $table->date('date');
            $table->string('name');
            $table->string('category');
            $table->string('description')->nullable();
            $table->integer('amount');

            $table->foreignId('payment_method_id')->constrained('payment_methods');
            $table->foreignId('parent_transaction_id')->nullable()->constrained('transactions');
            $table->unsignedInteger('parent_transaction_order')->default(0);
            $table->foreignId('nordigen_transaction_id')->nullable()->constrained('nordigen_transactions');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
