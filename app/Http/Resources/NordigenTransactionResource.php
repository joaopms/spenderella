<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NordigenTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'date' => $this->booking_date->toDateString(),
            'amount' => Transaction::formatAmount($this->amount),
            'description' => $this->description,
            'account' => NordigenAccountResource::make($this->account),
        ];
    }
}
