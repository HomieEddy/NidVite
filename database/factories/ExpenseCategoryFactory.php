<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->lexify('exp-????'),
            'label_en' => $this->faker->word(),
            'label_fr' => $this->faker->word(),
        ];
    }
}
