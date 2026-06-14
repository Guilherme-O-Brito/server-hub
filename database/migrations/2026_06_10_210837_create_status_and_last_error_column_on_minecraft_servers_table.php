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
            $table->string('status')->nullable();
            $table->string('last_error')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('minecraft_servers', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('last_error');
        });
    }
};
