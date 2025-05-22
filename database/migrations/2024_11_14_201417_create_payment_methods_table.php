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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();

            $table->string('name');
            $table->enum('type', ['bank_account', 'credit_card', 'cash'])->nullable();
            $table->foreignId('nordigen_account_id')->nullable()->constrained('nordigen_accounts');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
