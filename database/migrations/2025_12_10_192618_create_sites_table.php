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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->string('domain');
            $table->string('system_user'); // Unix user for the site
            $table->string('web_directory')->default('/home/{user}/sites/{domain}');
            $table->string('repository_url')->nullable();
            $table->string('repository_branch')->default('main');
            $table->string('php_version')->default('8.2');
            $table->text('environment_variables')->nullable(); // Encrypted JSON
            $table->boolean('is_active')->default(true);
            $table->string('current_release')->nullable(); // Symlink to current release
            $table->timestamps();
            
            $table->unique(['server_id', 'domain']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
