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
        Schema::create('execution_slots', function (Blueprint $table) {
            $table->id();
            $table->integer('slot_number')->unique();
            $table->integer('external_port')->unique();
            $table->string('service_name')->unique();
            $table->string('game_name')->nullable();
            $table->string('status');
            $table->nullableMorphs('server');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('execution_slots');
    }
};
