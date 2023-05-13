<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nordigen_requisitions', function (Blueprint $table) {
            $table->id('id');
            $table->uuid('uuid')->unique();

            $table->foreignId('agreement_id')->constrained('nordigen_agreements');

            $table->string('nordigen_id')->nullable();
            $table->string('link')->nullable();

            $table->timestamp('nordigen_created_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nordigen_requisitions');
    }
};
