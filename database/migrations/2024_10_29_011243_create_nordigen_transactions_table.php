<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nordigen_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('account_id')->constrained('nordigen_accounts');

            $table->string('bank_id')->nullable(); // transactionId
            $table->string('nordigen_id')->nullable(); // internalTransactionId

            $table->timestamp('booking_date');
            $table->timestamp('value_date');

            $table->integer('amount'); // transactionAmount.amount
            $table->string('currency', 3); // transactionAmount.currency

            $table->string('description'); // remittanceInformationUnstructured

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nordigen_transactions');
    }
};
