<?php

namespace Database\Factories;

use App\Models\NordigenAccount;
use App\Models\NordigenSyncResult;
use App\Models\NordigenTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class NordigenSyncResultFactory extends Factory
{
    protected $model = NordigenSyncResult::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'batch_id' => $this->faker->uuid(),
            'nordigen_account_id' => NordigenAccount::factory(),
            'attempt' => $this->faker->randomDigitNotZero(),
            'success' => true,
            'transaction_ids' => [NordigenTransaction::factory()->create()->id],
        ];
    }
}
