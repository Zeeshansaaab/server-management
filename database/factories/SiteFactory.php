<?php

namespace Database\Factories;

use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Site>
 */
class SiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $domain = fake()->domainName();
        $user = 'forge_' . Str::slug($domain);

        return [
            'server_id' => Server::factory(),
            'domain' => $domain,
            'system_user' => $user,
            'web_directory' => '/home/' . $user . '/sites/' . $domain,
            'repository_url' => 'https://github.com/laravel/laravel.git',
            'repository_branch' => 'main',
            'php_version' => '8.2',
            'is_active' => true,
        ];
    }
}
