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
        Schema::table('minecraft_versions', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->after('version')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('minecraft_versions', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
