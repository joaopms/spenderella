<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasVersion4Uuids as HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'institution_id',
        'institution_name',
        'institution_bic',
        'name',
        'iban',
        'is_credit',
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

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'id', 'nordigen_account_id');
    }

    public function getAgreements(): Builder
    {
        return NordigenAgreement::whereHas('requisition.accounts', function (Builder $query) {
            $query->where('nordigen_accounts.id', $this->id);
        });
    }

    public function lastAgreement(): Attribute
    {
        return new Attribute(
            get: fn () => $this->getAgreements()->latest()->first()
        );
    }

    public function validUntil(): Attribute
    {
        return new Attribute(
            get: fn () => $this->last_agreement->access_valid_until
        );
    }

    public function canSyncTransactions(): bool
    {
        return ! $this->last_agreement->isExpired();
    }
}
