<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'action' => $this->faker->randomElement(['create', 'update', 'delete']),
            'entity_type' => 'ip_address',
            'entity_id' => $this->faker->numberBetween(1, 100),
            'old_values' => null,
            'new_values' => ['label' => $this->faker->words(2, true)],
            'user_id' => $this->faker->numberBetween(1, 10),
            'user_email' => $this->faker->email(),
            'session_id' => $this->faker->uuid(),
            'ip_address' => $this->faker->ipv4(),
            'hash' => $this->faker->sha256(),
        ];
    }
}
