<?php

namespace Tests\Feature\Servers\Minecraft;

use App\Actions\MinecraftConsole\SendMinecraftServerCommandAction;
use App\Exceptions\MinecraftServerStateException;
use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class SendMinecraftServerCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_send_command_and_get_the_console_response(): void
    {
        $owner = User::factory()->create();
        $server = MinecraftServer::factory()->for($owner, 'owner')->create(['status' => 'running']);
        $action = $this->createMock(SendMinecraftServerCommandAction::class);
        $action->expects($this->once())
            ->method('execute')
            ->with($this->callback(fn (MinecraftServer $argument) => $argument->is($server)), 'list')
            ->willReturn('There are 0 of a max of 20 players online.');
        $this->app->instance(SendMinecraftServerCommandAction::class, $action);

        $response = $this->actingAs($owner)->postJson(
            "/servers/minecraft/{$server->id}/console/commands",
            ['command' => 'list']
        );

        $response->assertOk()->assertExactJson([
            'response' => 'There are 0 of a max of 20 players online.',
        ]);
    }

    public function test_guest_receives_401_and_command_is_not_executed(): void
    {
        $server = MinecraftServer::factory()->create(['status' => 'running']);
        $action = $this->createMock(SendMinecraftServerCommandAction::class);
        $action->expects($this->never())->method('execute');
        $this->app->instance(SendMinecraftServerCommandAction::class, $action);

        $this->postJson("/servers/minecraft/{$server->id}/console/commands", ['command' => 'list'])
            ->assertUnauthorized();
    }

    public function test_authenticated_user_without_console_permission_receives_403(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $server = MinecraftServer::factory()->for($owner, 'owner')->create(['status' => 'running']);
        $server->admins()->attach($user->id);
        $action = $this->createMock(SendMinecraftServerCommandAction::class);
        $action->expects($this->never())->method('execute');
        $this->app->instance(SendMinecraftServerCommandAction::class, $action);

        $this->actingAs($user)->postJson(
            "/servers/minecraft/{$server->id}/console/commands",
            ['command' => 'list']
        )->assertForbidden();
    }

    #[DataProvider('invalidCommands')]
    public function test_invalid_command_payload_returns_422_without_calling_action(array $payload): void
    {
        $owner = User::factory()->create();
        $server = MinecraftServer::factory()->for($owner, 'owner')->create(['status' => 'running']);
        $action = $this->createMock(SendMinecraftServerCommandAction::class);
        $action->expects($this->never())->method('execute');
        $this->app->instance(SendMinecraftServerCommandAction::class, $action);

        $this->actingAs($owner)->postJson(
            "/servers/minecraft/{$server->id}/console/commands",
            $payload
        )->assertUnprocessable()->assertJsonValidationErrors('command');
    }

    public static function invalidCommands(): array
    {
        return [
            'missing command' => [[]],
            'null command' => [['command' => null]],
            'empty command' => [['command' => '']],
            'non string command' => [['command' => ['list']]],
            'too long' => [['command' => str_repeat('a', 257)]],
            'control character' => [['command' => "say hello\nthere"]],
            'blocked stop command' => [['command' => 'stop']],
            'blocked slash command case insensitive' => [['command' => '/STOP']],
            'blocked namespaced command' => [['command' => 'minecraft:stop']],
            'blocked leading whitespace command' => [['command' => '  /reload']],
            'blocked command with arguments' => [['command' => 'whitelist list']],
            'blocked command namespace after plugin prefix' => [['command' => 'minecraft:execute as Steve run say hi']],
            'blocked restart' => [['command' => 'restart']],
            'blocked save-off' => [['command' => 'save-off']],
            'blocked function' => [['command' => 'function foo:bar']],
            'blocked debug' => [['command' => 'debug start']],
            'blocked jfr' => [['command' => 'jfr start']],
            'blocked perf' => [['command' => 'perf']],
        ];
    }

    public function test_command_at_maximum_length_is_accepted(): void
    {
        $owner = User::factory()->create();
        $server = MinecraftServer::factory()->for($owner, 'owner')->create(['status' => 'running']);
        $command = 'a'.str_repeat('b', 255);
        $action = $this->createMock(SendMinecraftServerCommandAction::class);
        $action->expects($this->once())
            ->method('execute')
            ->with($this->callback(fn (MinecraftServer $argument) => $argument->is($server)), $command)
            ->willReturn('accepted');
        $this->app->instance(SendMinecraftServerCommandAction::class, $action);

        $this->actingAs($owner)->postJson(
            "/servers/minecraft/{$server->id}/console/commands",
            ['command' => $command]
        )->assertOk()->assertExactJson(['response' => 'accepted']);
    }

    public function test_unknown_server_returns_404(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->postJson('/servers/minecraft/999999/console/commands', ['command' => 'list'])
            ->assertNotFound();
    }

    public function test_console_execution_exception_returns_502(): void
    {
        $owner = User::factory()->create();
        $server = MinecraftServer::factory()->for($owner, 'owner')->create(['status' => 'running']);
        $action = $this->createMock(SendMinecraftServerCommandAction::class);
        $action->expects($this->once())->method('execute')->willThrowException(new RuntimeException('RCON unavailable'));
        $this->app->instance(SendMinecraftServerCommandAction::class, $action);

        $this->actingAs($owner)->postJson(
            "/servers/minecraft/{$server->id}/console/commands",
            ['command' => 'list']
        )->assertStatus(502);
    }

    public function test_non_running_server_returns_409_with_state_error_message(): void
    {
        $owner = User::factory()->create();
        $server = MinecraftServer::factory()->for($owner, 'owner')->create(['status' => 'stopped']);
        $action = $this->createMock(SendMinecraftServerCommandAction::class);
        $action->expects($this->once())
            ->method('execute')
            ->willThrowException(new MinecraftServerStateException('Minecraft server is not running.'));
        $this->app->instance(SendMinecraftServerCommandAction::class, $action);

        $this->actingAs($owner)->postJson(
            "/servers/minecraft/{$server->id}/console/commands",
            ['command' => 'list']
        )->assertStatus(409)->assertExactJson([
            'message' => 'Minecraft server is not running.',
        ]);
    }
}
