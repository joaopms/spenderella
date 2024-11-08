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
        Schema::create('nordigen_sync_results', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignUuid('batch_id')->constrained('job_batches');
            $table->foreignId('nordigen_account_id')->constrained('nordigen_accounts');
            $table->unsignedSmallInteger('attempt');

            $table->boolean('success');

            $table->json('transaction_ids')->nullable();
            $table->text('exception_message')->nullable();
            $table->text('exception_trace')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nordigen_sync_results');
    }
};
