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
        Schema::create('minecraft_servers', function (Blueprint $table) {
            $table->id();
            $table->string('server_name');
            $table->string('level_name'); // world name
            $table->string('motd');
            $table->tinyInteger('difficulty'); //in minecraft difficulty level goes from 0 to 3. peacefull, easy, medium and hard
            $table->boolean('force_gamemode');
            $table->boolean('allow_flight');
            // owner relation 1:N
            $table->foreignId('owner')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('minecraft_servers');
    }
};
