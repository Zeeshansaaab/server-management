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
        Schema::create('databases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('username');
            $table->text('password'); // Encrypted
            $table->string('host')->default('localhost');
            $table->integer('port')->default(3306);
            $table->timestamps();
            
            $table->unique(['server_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('databases');
    }
};
