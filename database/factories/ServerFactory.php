<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Server>
 */
class ServerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->word() . ' Server',
            'hostname' => fake()->domainName(),
            'ip_address' => fake()->ipv4(),
            'os' => 'Ubuntu 22.04',
            'ssh_user' => 'root',
            'ssh_port' => 22,
            'agent_token' => Str::random(64),
            'last_seen' => now(),
            'is_active' => true,
        ];
    }
}
