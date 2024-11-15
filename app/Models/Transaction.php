<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
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

    public function nordigenTransaction(): HasOne
    {
        return $this->hasOne(NordigenTransaction::class, 'id', 'nordigen_transaction_id');
    }

    public function humanAmount(): Attribute
    {
        return Attribute::make(
            get: function ($ignored, array $attributes) {
                $formatter = new NumberFormatter(config('app.format_locale'), NumberFormatter::CURRENCY);

                // Make the number sign required when the number is positive
                $formatter->setTextAttribute(NumberFormatter::POSITIVE_PREFIX, '+');

                // TODO Don't hardcode EUR, maybe? Maybe store it in the PaymentMethod?
                return $formatter->formatCurrency($attributes['amount'] / 100, 'EUR');
            }
        );
    }
}
