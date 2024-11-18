<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use NumberFormatter;

class Transaction extends Model
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
        'date',
        'name',
        'category',
        'description',
        'amount',
        'payment_method_id',
        'parent_transaction_id',
        'parent_transaction_order',
        'nordigen_transaction_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function paymentMethod(): HasOne
    {
        return $this->hasOne(PaymentMethod::class, 'id', 'payment_method_id');
    }

    public function parentTransaction(): HasOne
    {
        return $this->hasOne(Transaction::class, 'id', 'parent_transaction_id');
    }

    public function splitTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'parent_transaction_id');
    }

    public function nordigenTransaction(): HasOne
    {
        return $this->hasOne(NordigenTransaction::class, 'id', 'nordigen_transaction_id');
    }

    public function scopeParent(Builder $query): void
    {
        $query->whereNull('parent_transaction_id');
    }

    // TODO Move this out of here
    public static function formatAmount(int $amount): string
    {
        $formatter = new NumberFormatter(config('app.format_locale'), NumberFormatter::CURRENCY);

        // Make the number sign required when the number is positive
        $formatter->setTextAttribute(NumberFormatter::POSITIVE_PREFIX, '+');

        // TODO Don't hardcode EUR, maybe? Maybe store it in the PaymentMethod?
        return $formatter->formatCurrency($amount / 100, 'EUR');
    }

    public function splitAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->splitTransactions()->sum('amount')
        );
    }

    public function amountAfterSplit(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->amount + $this->splitAmount
        );
    }
}
