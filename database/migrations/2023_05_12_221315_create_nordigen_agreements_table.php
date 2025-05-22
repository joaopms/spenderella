<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nordigen_agreements', function (Blueprint $table) {
            $table->id('id');
            $table->char('uuid', 36)->unique();

            $table->string('nordigen_id');
            $table->string('institution_id');

            $table->unsignedInteger('access_valid_for_days')->nullable();
            $table->timestamp('access_valid_until')->nullable();

            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('nordigen_created_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nordigen_agreements');
    }
};
