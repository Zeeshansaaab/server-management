<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('hostname');
            $table->string('ip_address');
            $table->string('os')->nullable();
            $table->string('ssh_user')->default('root');
            $table->integer('ssh_port')->default(22);
            $table->text('ssh_private_key')->nullable(); // Encrypted
            $table->string('ssh_public_key')->nullable();
            $table->string('agent_token')->unique(); // For agent registration
            $table->timestamp('last_seen')->nullable();
            $table->json('tags')->nullable();
            $table->bigInteger('free_disk_gb')->nullable();
            $table->string('cpu_info')->nullable();
            $table->bigInteger('memory_mb')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
