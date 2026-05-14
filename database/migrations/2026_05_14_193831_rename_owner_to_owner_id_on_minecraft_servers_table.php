<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('minecraft_servers', function (Blueprint $table) {
            // drop existing foreign key on `owner`, then add `owner_id` and copy the values
            $table->dropForeign(['owner']);
            $table->unsignedBigInteger('owner_id')->nullable();
        });

        DB::statement('UPDATE minecraft_servers SET owner_id = owner');

        Schema::table('minecraft_servers', function (Blueprint $table) {
            $table->dropColumn('owner');
            $table->foreign('owner_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('minecraft_servers', function (Blueprint $table) {
            // reverse: recreate `owner`, copy values back, then drop `owner_id` foreign
            $table->unsignedBigInteger('owner')->nullable();
        });

        DB::statement('UPDATE minecraft_servers SET owner = owner_id');

        Schema::table('minecraft_servers', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropColumn('owner_id');
            $table->foreign('owner')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
