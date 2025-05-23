<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NordigenAccountResource extends JsonResource
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
            'institutionName' => $this->institution_name,
            'iban' => $this->iban,
            'validUntil' => $this->valid_until->toDateString(),
            'isExpired' => $this->last_agreement->isExpired(),
        ];
    }
}
