<?php

namespace Tests\Feature;

use App\Models\Deployment;
use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteDeploymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_site(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson('/api/sites', [
            'server_id' => $server->id,
            'domain' => 'example.com',
            'repository_url' => 'https://github.com/laravel/laravel.git',
            'repository_branch' => 'main',
            'php_version' => '8.2',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'domain',
                'server_id',
            ]);

        $this->assertDatabaseHas('sites', [
            'domain' => 'example.com',
            'server_id' => $server->id,
        ]);
    }

    public function test_user_can_trigger_deployment(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        $site = Site::factory()->create(['server_id' => $server->id]);

        $response = $this->actingAs($user)->postJson('/api/deployments', [
            'site_id' => $site->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'site_id',
                'status',
            ]);

        $this->assertDatabaseHas('deployments', [
            'site_id' => $site->id,
            'status' => 'pending',
        ]);
    }

    public function test_deployment_has_logs(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        $site = Site::factory()->create(['server_id' => $server->id]);
        $deployment = Deployment::factory()->create(['site_id' => $site->id]);

        $response = $this->actingAs($user)->getJson("/api/deployments/{$deployment->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'logs',
            ]);
    }
}
