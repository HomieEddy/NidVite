<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 50);
        $unitCost = $this->faker->randomFloat(2, 10, 500);
        $subtotal = $quantity * $unitCost;
        $taxRate = 0.14975;
        $taxAmount = $subtotal * $taxRate;

        return [
            'repair_job_id' => null,
            'description' => $this->faker->words(3, true),
            'quantity' => $quantity,
            'unit' => 'unité',
            'unit_cost' => $unitCost,
            'subtotal' => round($subtotal, 2),
            'tax_rate' => $taxRate,
            'tax_amount' => round($taxAmount, 2),
            'total' => round($subtotal + $taxAmount, 2),
            'incurred_at' => now()->subDays(rand(1, 30)),
            'created_by' => null,
        ];
    }
}
