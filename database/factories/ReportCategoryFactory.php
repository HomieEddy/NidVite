<?php

namespace Database\Factories;

use App\Models\ReportCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportCategoryFactory extends Factory
{
    protected $model = ReportCategory::class;

    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->lexify('cat-????'),
            'label_en' => $this->faker->word(),
            'label_fr' => $this->faker->word(),
        ];
    }
}
