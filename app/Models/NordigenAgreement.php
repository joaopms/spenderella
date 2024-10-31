<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NordigenAgreement extends Model
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
        'institution_id',
        'nordigen_created_at',
        'access_valid_for_days',
    ];

    protected $casts = [
        'nordigen_created_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function requisition(): HasOne
    {
        return $this->hasOne(NordigenRequisition::class, 'agreement_id');
    }

    protected function accessValidForDays(): Attribute
    {
        // Automatically compute and set "access_valid_until" based on "access_valid_for_days"
        return Attribute::set(fn (int $accessValidForDays) => [
            'access_valid_for_days' => $accessValidForDays,
            'access_valid_until' => $this->accepted_at->addDays($accessValidForDays)->startOfDay(),
        ]);
    }

    public function isLocallySaved(): bool
    {
        return (bool) $this->accepted_at;
    }

    public function isExpired(): bool
    {
        return $this->access_valid_until->lessThanOrEqualTo(Carbon::now());
    }
}
