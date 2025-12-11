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
        Schema::table('sites', function (Blueprint $table) {
            $table->string('node_version')->nullable()->after('php_version');
            $table->string('framework')->nullable()->after('node_version'); // 'laravel', 'nextjs', 'react', 'nodejs', etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['node_version', 'framework']);
        });
    }
};
