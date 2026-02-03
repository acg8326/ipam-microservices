<?php

namespace Database\Factories;

use App\Models\IpAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

class IpAddressFactory extends Factory
{
    protected $model = IpAddress::class;

    public function definition(): array
    {
        return [
            'ip_address' => $this->faker->ipv4(),
            'label' => $this->faker->words(2, true) . ' Server',
            'comment' => $this->faker->optional()->sentence(),
            'created_by' => $this->faker->numberBetween(1, 10),
            'updated_by' => null,
        ];
    }

    public function ipv6(): static
    {
        return $this->state(fn (array $attributes) => [
            'ip_address' => $this->faker->ipv6(),
        ]);
    }
}
