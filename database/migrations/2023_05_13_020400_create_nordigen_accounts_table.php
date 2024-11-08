<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nordigen_accounts', function (Blueprint $table) {
            $table->id('id');
            $table->uuid('uuid')->unique();

            $table->string('nordigen_id');
            $table->string('currency', 3);

            $table->string('institution_id');
            $table->string('institution_name');
            $table->string('institution_bic');

            $table->string('name')->nullable();
            $table->string('iban')->nullable();

            $table->timestamps();
        });

        Schema::create('nordigen_accounts_requisitions', function (Blueprint $table) {
            $table->foreignId('account_id')->constrained('nordigen_accounts');
            $table->foreignId('requisition_id')->constrained('nordigen_requisitions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nordigen_accounts_requisitions');
        Schema::dropIfExists('nordigen_accounts');
    }
};
