<?php

namespace App\Services;

use App\Models\Server;
use App\Models\Site;
use App\Models\Database;
use App\Services\SshService;
use Illuminate\Support\Str;

class DatabaseService
{
    public function __construct(
        private SshService $sshService
    ) {}

    /**
     * Create a database and user on the server.
     */
    public function createDatabase(Server $server, ?Site $site = null, ?string $name = null, ?string $username = null): Database
    {
        $name = $name ?? 'db_' . Str::random(8);
        $username = $username ?? 'user_' . Str::random(8);
        $password = Str::random(32);

        $server = $server->fresh();
        
        // Create database and user via MySQL commands
        $commands = [
            "mysql -e \"CREATE DATABASE IF NOT EXISTS $name;\"",
            "mysql -e \"CREATE USER IF NOT EXISTS '$username'@'localhost' IDENTIFIED BY '$password';\"",
            "mysql -e \"GRANT ALL PRIVILEGES ON $name.* TO '$username'@'localhost';\"",
            "mysql -e \"FLUSH PRIVILEGES;\"",
        ];

        foreach ($commands as $command) {
            $result = $this->sshService->execute($server, $command, true);
            if (!$result['success']) {
                throw new \Exception("Failed to create database: " . $result['error']);
            }
        }

        $database = new Database([
            'server_id' => $server->id,
            'site_id' => $site?->id,
            'name' => $name,
            'username' => $username,
            'host' => 'localhost',
            'port' => 3306,
        ]);
        $database->setPassword($password);
        $database->save();

        return $database;
    }

    /**
     * Delete a database.
     */
    public function deleteDatabase(Database $database): bool
    {
        $server = $database->server;
        
        $commands = [
            "mysql -e \"DROP DATABASE IF EXISTS {$database->name};\"",
            "mysql -e \"DROP USER IF EXISTS '{$database->username}'@'localhost';\"",
            "mysql -e \"FLUSH PRIVILEGES;\"",
        ];

        foreach ($commands as $command) {
            $result = $this->sshService->execute($server, $command, true);
            if (!$result['success']) {
                return false;
            }
        }

        $database->delete();
        return true;
    }
}

