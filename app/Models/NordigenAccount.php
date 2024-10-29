<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

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

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(NordigenRequisition::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(NordigenTransaction::class, 'account_id');
    }

    public function agreement(): HasOneThrough
    {
        return $this->hasOneThrough(
            NordigenAgreement::class,
            NordigenRequisition::class,
            'agreement_id',
            'id',
            'requisition_id',
            'agreement_id'
        );
    }

    public function canSyncTransactions(): bool
    {
        return $this->agreement->isExpired();
    }
}
