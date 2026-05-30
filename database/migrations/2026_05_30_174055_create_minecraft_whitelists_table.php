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
        Schema::create('minecraft_whitelists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('minecraft_servers')->cascadeOnDelete();
            $table->string('nickname', 16);
            $table->timestamps();
            $table->unique(['server_id', 'nickname']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('minecraft_whitelists');
    }
};
