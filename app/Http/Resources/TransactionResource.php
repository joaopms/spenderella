<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'name' => $this->name,
            'date' => $this->date->toDateString(),
            'paymentMethod' => PaymentMethodSelectionResource::make($this->paymentMethod),
            'category' => $this->category,
            'description' => $this->description,
            'amount' => Transaction::formatAmount($this->amount),
            'split' => TransactionResource::collection($this->splitTransactions),
            'amountAfterSplit' => Transaction::formatAmount($this->amountAfterSplit),
            'linkedTransactionUuid' => $this->nordigenTransaction?->uuid,
        ];
    }
}
