<?php

namespace Database\Factories;

use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Deployment>
 */
class DeploymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'status' => fake()->randomElement(['pending', 'running', 'success', 'failed']),
            'commit_hash' => fake()->sha1(),
            'started_at' => now(),
            'completed_at' => now()->addMinutes(5),
        ];
    }
}
