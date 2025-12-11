<?php

namespace App\Services;

use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class SshService
{
    /**
     * Execute a command on a server via SSH.
     *
     * @param Server $server
     * @param string $command
     * @param bool $sudo
     * @return array{success: bool, output: string, error: string, exit_code: int}
     */
    public function execute(Server $server, string $command, bool $sudo = false): array
    {
        $sshKey = $server->getDecryptedSshPrivateKey();
        
        if (!$sshKey) {
            return [
                'success' => false,
                'output' => '',
                'error' => 'SSH private key not configured',
                'exit_code' => 1,
            ];
        }

        // Write SSH key to temporary file
        // Normalize line endings (convert CRLF to LF)
        $sshKey = str_replace(["\r\n", "\r"], "\n", $sshKey);
        
        // Clean the key: remove any extra whitespace but preserve structure
        $sshKey = trim($sshKey);
        
        // Ensure key ends with newline (required by OpenSSH)
        if (!str_ends_with($sshKey, "\n")) {
            $sshKey .= "\n";
        }
        
        $keyFile = tempnam(sys_get_temp_dir(), 'forge_ssh_key_');
        
        // Write key to file
        $bytesWritten = file_put_contents($keyFile, $sshKey);
        if ($bytesWritten === false) {
            return [
                'success' => false,
                'output' => '',
                'error' => 'Failed to write SSH key to temporary file',
                'exit_code' => 1,
            ];
        }
        
        // Set restrictive permissions (read/write for owner only)
        chmod($keyFile, 0600);
        
        // Verify the file was written correctly
        $fileContents = file_get_contents($keyFile);
        if ($fileContents !== $sshKey) {
            @unlink($keyFile);
            return [
                'success' => false,
                'output' => '',
                'error' => 'SSH key file verification failed - file contents do not match',
                'exit_code' => 1,
            ];
        }
        
        // Validate key file with ssh-keygen (this will catch format issues)
        $validateCommand = sprintf('ssh-keygen -l -f %s 2>&1', escapeshellarg($keyFile));
        $validateOutput = shell_exec($validateCommand);
        $validateExitCode = shell_exec("echo $?");
        
        // Log key file details for debugging
        Log::debug('SSH key file created', [
            'key_file' => $keyFile,
            'key_length' => strlen($sshKey),
            'file_size' => filesize($keyFile),
            'file_permissions' => substr(sprintf('%o', fileperms($keyFile)), -4),
            'key_starts_with' => substr($sshKey, 0, 40),
            'key_ends_with' => substr(trim($sshKey), -40),
            'validation_output' => trim($validateOutput),
            'validation_exit_code' => trim($validateExitCode),
        ]);
        
        // If validation fails, it might be encrypted or corrupted
        // But we'll still try the connection as the error message will be more specific

        // Build SSH command
        // For simple commands, don't wrap in bash -lc
        // For complex commands or sudo, we might need shell wrapping
        $remoteCommand = $command;
        if ($sudo) {
            $remoteCommand = "sudo $command";
        }
        
        $sshCommand = sprintf(
            'ssh -i %s -p %d -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o LogLevel=ERROR -o ConnectTimeout=10 -o BatchMode=yes %s@%s %s',
            escapeshellarg($keyFile),
            $server->ssh_port,
            escapeshellarg($server->ssh_user),
            escapeshellarg($server->ip_address),
            escapeshellarg($remoteCommand)
        );
        
        Log::debug('SSH command', [
            'command' => $sshCommand,
            'server' => $server->ip_address,
            'user' => $server->ssh_user,
            'key_file' => $keyFile,
        ]);

        try {
            $process = Process::fromShellCommandline($sshCommand);
            $process->setTimeout(300); // 5 minutes
            $process->run();

            $output = $process->getOutput();
            $error = $process->getErrorOutput();
            $exitCode = $process->getExitCode();
            
            // Log detailed results
            Log::debug('SSH execution result', [
                'exit_code' => $exitCode,
                'output_length' => strlen($output),
                'error_length' => strlen($error),
                'output_preview' => substr($output, 0, 200),
                'error_preview' => substr($error, 0, 200),
            ]);

            // Update last_seen if connection was successful (even if command failed, connection worked)
            // Only update if we got a response (exitCode is not null), meaning connection worked
            if ($exitCode !== null && $server->exists) {
                try {
                    $server->update(['last_seen' => now()]);
                    $server->refresh();
                } catch (\Exception $e) {
                    // Log but don't fail the operation
                    Log::debug('Failed to update last_seen', [
                        'server_id' => $server->id ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Clean up key file
            @unlink($keyFile);

            return [
                'success' => $exitCode === 0,
                'output' => $output,
                'error' => $error,
                'exit_code' => $exitCode,
            ];
        } catch (\Exception $e) {
            @unlink($keyFile);
            
            Log::error('SSH execution failed', [
                'server_id' => $server->id,
                'command' => $command,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage(),
                'exit_code' => 1,
            ];
        }
    }

    /**
     * Test SSH connection to server.
     */
    public function testConnection(Server $server): array
    {
        $result = $this->execute($server, 'echo "OK"');
        
        return [
            'success' => $result['success'] && trim($result['output']) === 'OK',
            'output' => $result['output'],
            'error' => $result['error'],
            'exit_code' => $result['exit_code'],
        ];
    }
}

