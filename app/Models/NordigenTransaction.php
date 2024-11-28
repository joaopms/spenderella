<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use NumberFormatter;

class NordigenTransaction extends Model
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
        'bank_id',
        'nordigen_id',
        'entry_reference',
        'booking_date',
        'value_date',
        'amount',
        'currency',
        'description',
    ];

    protected $casts = [
        'booking_date' => 'datetime',
        'value_date' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(NordigenAccount::class);
    }

    public function linkedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function humanAmount(): Attribute
    {
        return Attribute::make(
            get: function ($ignored, array $attributes) {
                $formatter = new NumberFormatter(config('app.format_locale'), NumberFormatter::CURRENCY);

                return $formatter->formatCurrency($attributes['amount'] / 100, $this->currency);
            }
        );
    }
}
