<?php

namespace Database\Factories;

use App\Models\NordigenAccount;
use App\Models\NordigenTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class NordigenTransactionFactory extends Factory
{
    protected $model = NordigenTransaction::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'booking_date' => Carbon::now(),
            'value_date' => Carbon::now(),
            'amount' => $this->faker->randomNumber(),
            'currency' => 'EUR',
            'description' => $this->faker->text(),
            'raw' => '', // TODO

            'account_id' => NordigenAccount::factory(),
        ];
    }
}
