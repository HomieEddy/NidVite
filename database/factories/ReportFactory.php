<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\ReportCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'reporter_email' => $this->faker->safeEmail(),
            'preferred_locale' => 'fr',
            'status' => 'received',
            'priority' => 'normal',
            'category_id' => ReportCategory::factory(),
            'description' => $this->faker->sentence(),
            'ip_address_hash' => hash('sha256', $this->faker->ipv4()),
            'user_agent_hash' => hash('sha256', $this->faker->userAgent()),
            'is_spam' => false,
        ];
    }
}
