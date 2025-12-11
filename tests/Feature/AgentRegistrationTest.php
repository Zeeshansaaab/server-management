<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_register_with_valid_token(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);

        $response = $this->postJson('/api/agent/register', [
            'agent_token' => $server->agent_token,
            'hostname' => 'test.example.com',
            'ip_address' => '192.168.1.100',
            'os' => 'Ubuntu 22.04',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'server_id',
                'status',
            ]);

        $this->assertDatabaseHas('servers', [
            'id' => $server->id,
            'hostname' => 'test.example.com',
        ]);
    }

    public function test_agent_registration_fails_with_invalid_token(): void
    {
        $response = $this->postJson('/api/agent/register', [
            'agent_token' => 'invalid_token',
            'hostname' => 'test.example.com',
            'ip_address' => '192.168.1.100',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Invalid agent token',
            ]);
    }

    public function test_agent_can_report_metrics(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);

        $response = $this->postJson('/api/agent/metrics', [
            'agent_token' => $server->agent_token,
            'cpu_usage' => 25.5,
            'memory_used_mb' => 2048,
            'memory_total_mb' => 4096,
            'disk_used_gb' => 50,
            'disk_total_gb' => 100,
            'load_average_1m' => 0.5,
            'load_average_5m' => 0.6,
            'load_average_15m' => 0.7,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
            ]);

        $this->assertDatabaseHas('server_metrics', [
            'server_id' => $server->id,
        ]);
    }
}
