<?php

namespace Database\Factories;

use App\Models\RepairJob;
use Illuminate\Database\Eloquent\Factories\Factory;

class RepairJobFactory extends Factory
{
    protected $model = RepairJob::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'status' => 'planned',
            'created_by' => null,
        ];
    }
}
