<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string[]> */
    private static array $changes = [
        'nordigen_agreements' => ['uuid'],
        'nordigen_requisitions' => ['uuid'],
        'nordigen_accounts' => ['uuid'],
        'nordigen_transactions' => ['uuid'],
        'nordigen_sync_results' => ['uuid'],
        'payment_methods' => ['uuid'],
        'transactions' => ['uuid'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::$changes as $table => $columns) {
            Schema::table($table, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    // Convert to uuid
                    $table->uuid($column)->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::$changes as $table => $columns) {
            Schema::table($table, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    // Convert back to char(36)
                    $table->char($column, 36)->change();
                }
            });
        }
    }
};
