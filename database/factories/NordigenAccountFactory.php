<?php

namespace Database\Factories;

use App\Models\NordigenAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class NordigenAccountFactory extends Factory
{
    protected $model = NordigenAccount::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'nordigen_id' => $this->faker->uuid(),
            'currency' => 'EUR',
            'institution_id' => $this->faker->word(),
            'institution_name' => $this->faker->name(),
            'institution_bic' => $this->faker->word(),
            'is_credit' => $this->faker->boolean(),
        ];
    }
}
