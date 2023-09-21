<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NordigenRequisition extends Model
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
        'link',
        'nordigen_created_at',
    ];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(NordigenAgreement::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(NordigenAccount::class, 'requisition_id');
    }

    public function hasAccounts(): bool
    {
        return $this->accounts()->exists();
    }
}
