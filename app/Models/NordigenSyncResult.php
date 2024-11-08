<?php

namespace App\Models;

use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class NordigenSyncResult extends Model
{
    use HasUuids;

    private NordigenAccount $account;

    /** @var NordigenTransaction[] */
    private array $transactions;

    protected $hidden = [
        'id',
    ];

    protected $primaryKey = 'id';

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected $fillable = [
        'batch_id',
        'nordigen_account_id',
        'attempt',
        'success',
        'transaction_ids',
        'exception_message',
        'exception_trace',
    ];

    protected $casts = [
        'transaction_ids' => 'array',
    ];

    public function getTransactions()
    {
        return $this->transactions ?? [];
    }

    public function getAccount()
    {
        return $this->account;
    }

    public function hasTransactions(): bool
    {
        return count($this->transaction_ids);
    }

    /**
     * @param  NordigenTransaction[]  $transactions
     */
    public static function success(
        Batch $batch,
        NordigenAccount $account,
        int $attempts,
        array $transactions
    ): NordigenSyncResult {
        return NordigenSyncResult::create([
            'batch_id' => $batch->id,
            'nordigen_account_id' => $account->id,
            'attempt' => $attempts,
            'success' => true,
            'transaction_ids' => collect($transactions)->pluck('id'),
        ]);
    }

    public static function fail(
        Batch $batch,
        NordigenAccount $account,
        int $attempts,
        Throwable $exception
    ): NordigenSyncResult {
        return NordigenSyncResult::create([
            'batch_id' => $batch->id,
            'nordigen_account_id' => $account->id,
            'attempt' => $attempts,
            'success' => false,
            'exception_message' => $exception->getMessage(),
            'exception_trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * @return NordigenSyncResult[]
     */
    public static function getByBatchId(string $batchId): array
    {
        // Get the last attempt for every account
        $results = NordigenSyncResult::whereIn(
            'id',
            function (Builder $query) use ($batchId) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('nordigen_sync_results')
                    ->where('batch_id', $batchId)
                    ->groupBy('nordigen_account_id');
            })
            ->get();

        // Get all the new transactions from the database
        $transactionIds = $results->pluck('transaction_ids')->flatten();
        $transactions = NordigenTransaction::findMany($transactionIds);

        $accountIds = $results->pluck('nordigen_account_id');
        $accounts = NordigenAccount::findMany($accountIds);

        // Inject the transactions into the result
        foreach ($results as $result) {
            $resultTransactionIds = $result->transaction_ids;

            $result->account = $accounts->find($result->nordigen_account_id);
            $result->transactions = $transactions->whereIn('id', $resultTransactionIds)->all();
        }

        return $results->all();
    }
}
