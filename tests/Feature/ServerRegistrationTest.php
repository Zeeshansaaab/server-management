<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_server(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/servers', [
            'name' => 'Test Server',
            'hostname' => 'test.example.com',
            'ip_address' => '192.168.1.100',
            'ssh_user' => 'root',
            'ssh_port' => 22,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'hostname',
                'ip_address',
                'agent_token',
            ]);

        $this->assertDatabaseHas('servers', [
            'name' => 'Test Server',
            'user_id' => $user->id,
        ]);
    }

    public function test_server_has_unique_agent_token(): void
    {
        $user = User::factory()->create();

        $server1 = Server::factory()->create(['user_id' => $user->id]);
        $server2 = Server::factory()->create(['user_id' => $user->id]);

        $this->assertNotEquals($server1->agent_token, $server2->agent_token);
    }

    public function test_user_can_list_their_servers(): void
    {
        $user = User::factory()->create();
        $servers = Server::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/servers');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }
}
