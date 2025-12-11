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
        Schema::create('deployment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deployment_id')->constrained()->onDelete('cascade');
            $table->string('step'); // git_pull, composer_install, npm_build, migrate, etc.
            $table->text('output');
            $table->string('level')->default('info'); // info, error, warning
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployment_logs');
    }
};
