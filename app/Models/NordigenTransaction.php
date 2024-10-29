<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'booking_date',
        'value_date',
        'amount',
        'currency',
        'description',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(NordigenAccount::class);
    }
}
