<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NordigenAccount extends Model
{
    use HasUuids;

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
        'nordigen_id',
        'currency',
        'iban',
        'name',
    ];

    public function requisitions(): BelongsToMany
    {
        return $this->belongsToMany(
            NordigenRequisition::class,
            'nordigen_accounts_requisitions',
            'account_id',
            'requisition_id',
        );
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(NordigenTransaction::class, 'account_id');
    }

    public function getAgreements(): Builder
    {
        return NordigenAgreement::whereHas('requisition.accounts', function (Builder $query) {
            $query->where('nordigen_accounts.id', $this->id);
        });
    }

    public function getLastAgreement(): NordigenAgreement
    {
        /** @var NordigenAgreement $latest */
        $latest = $this->getAgreements()->latest()->first();

        return $latest;
    }

    public function canSyncTransactions(): bool
    {
        return $this->getLastAgreement()->isExpired();
    }
}
