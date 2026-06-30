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
        Schema::table('minecraft_servers', function (Blueprint $table) {
            $table->foreignId('minecraft_version_id')->constrained('minecraft_versions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('minecraft_servers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('minecraft_version_id');
        });
    }
};
