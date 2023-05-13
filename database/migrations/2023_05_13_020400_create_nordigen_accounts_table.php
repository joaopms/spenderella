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

            $table->foreignId('requisition_id')->constrained('nordigen_requisitions');

            $table->string('nordigen_id');
            $table->string('currency', 3);

            $table->string('iban')->nullable();
            $table->string('name')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nordigen_accounts');
    }
};
