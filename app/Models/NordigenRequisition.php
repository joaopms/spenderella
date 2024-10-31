<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(
            NordigenAccount::class,
            'nordigen_accounts_requisitions',
            'requisition_id',
            'account_id',
        );
    }
    
    public function hasAccounts(): bool
    {
        return $this->accounts()->exists();
    }
}
