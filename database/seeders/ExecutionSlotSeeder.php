<?php

namespace Database\Seeders;

use App\Models\ExecutionSlot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExecutionSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ExecutionSlot::factory()->count(4)->create();
    }
}
