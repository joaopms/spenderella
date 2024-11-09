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
        Schema::table('nordigen_transactions', function (Blueprint $table) {
            $table->string('entry_reference')->nullable()->after('nordigen_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nordigen_transactions', function (Blueprint $table) {
            $table->dropColumn('entry_reference');
        });
    }
};
