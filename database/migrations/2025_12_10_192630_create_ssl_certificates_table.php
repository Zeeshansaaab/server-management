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
        Schema::create('ssl_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->string('domain');
            $table->string('status')->default('pending'); // pending, active, expired, failed
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('certificate_path')->nullable();
            $table->text('private_key_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssl_certificates');
    }
};
