<?php

namespace Database\Seeders;

use App\Models\MinecraftOperator;
use App\Models\MinecraftServer;
use App\Models\MinecraftWhitelist;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MinecraftServerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@serverhub.com')->first();
        $user = User::where('email', 'test@serverhub.com')->first();

        $this->createServers($admin);
        $this->createServers($user);

    }

    protected function createServers(User $user): void
    {
        for ($i = 0; $i < 5; $i++) {
            $server = MinecraftServer::factory()->create([
                'owner_id' => $user->id
            ]);

            MinecraftWhitelist::factory()->count(3)->create([
                'minecraft_server_id' => $server->id
            ]);

            MinecraftOperator::factory()->count(3)->create([
                'minecraft_server_id' => $server->id
            ]);
        }
    }
}
