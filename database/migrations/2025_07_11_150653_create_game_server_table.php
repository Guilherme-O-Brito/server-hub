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
        Schema::create('gameservers', function (Blueprint $table) {
            $table->id();
            $table->char('name', length: 100);
            $table->unsignedTinyInteger('game_type');
            $table->text('config')->charset('binary');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // relacionamento onde um server tem apenas um dono (user)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gameservers');
    }
};
