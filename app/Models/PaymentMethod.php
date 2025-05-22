<?php

namespace App\Models;

use App\Enums\PaymentMethodType;
use Illuminate\Database\Eloquent\Concerns\HasVersion4Uuids as HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentMethod extends Model
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
        'name',
        'type',
        'nordigen_account_id',
    ];

    protected $casts = [
        'type' => PaymentMethodType::class,
    ];

    public function nordigenAccount(): HasOne
    {
        return $this->hasOne(NordigenAccount::class, 'id', 'nordigen_account_id');
    }
}
