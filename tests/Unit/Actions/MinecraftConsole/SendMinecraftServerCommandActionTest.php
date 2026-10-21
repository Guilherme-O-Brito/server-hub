<?php

namespace Tests\Unit\Actions\MinecraftConsole;

use App\Actions\MinecraftConsole\SendMinecraftServerCommandAction;
use App\Exceptions\MinecraftServerStateException;
use App\Models\MinecraftServer;
use App\Models\User;
use App\Services\Minecraft\MinecraftRconService;
use App\enums\MinecraftServerStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class SendMinecraftServerCommandActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_sends_command_to_the_server_rcon_and_returns_console_response(): void
    {
        $owner = User::factory()->create();
        $server = MinecraftServer::factory()->for($owner, 'owner')->create([
            'status' => MinecraftServerStatus::Running,
        ]);
        $rcon = $this->createMock(MinecraftRconService::class);
        $rcon->expects($this->once())
            ->method('execute')
            ->with("minecraft-{$server->id}-console-service", 'say hello')
            ->willReturn(' [Server] hello');

        $response = (new SendMinecraftServerCommandAction($rcon))->execute($server, 'say hello');

        $this->assertSame(' [Server] hello', $response);
    }

    #[DataProvider('nonRunningStatuses')]
    public function test_execute_rejects_non_running_server_before_calling_rcon(MinecraftServerStatus $status): void
    {
        $owner = User::factory()->create();
        $server = MinecraftServer::factory()->for($owner, 'owner')->create(['status' => $status]);
        $rcon = $this->createMock(MinecraftRconService::class);
        $rcon->expects($this->never())->method('execute');

        try {
            (new SendMinecraftServerCommandAction($rcon))->execute($server, 'list');
            $this->fail('Expected a state exception for a non-running server.');
        } catch (MinecraftServerStateException $exception) {
            $this->assertSame('Minecraft server is not running.', $exception->getMessage());
            $this->assertSame(409, $exception->statusCode());
        }
    }

    public static function nonRunningStatuses(): array
    {
        return [
            'provisioning' => [MinecraftServerStatus::Provisioning],
            'stopped' => [MinecraftServerStatus::Stopped],
            'starting' => [MinecraftServerStatus::Starting],
            'stopping' => [MinecraftServerStatus::Stopping],
            'restarting' => [MinecraftServerStatus::Restarting],
            'failed' => [MinecraftServerStatus::Failed],
            'deleting' => [MinecraftServerStatus::Deleting],
            'delete failed' => [MinecraftServerStatus::DeleteFailed],
        ];
    }

    public function test_execute_refreshes_server_state_before_sending_command(): void
    {
        $owner = User::factory()->create();
        $server = MinecraftServer::factory()->for($owner, 'owner')->create([
            'status' => MinecraftServerStatus::Running,
        ]);
        MinecraftServer::query()->whereKey($server->id)->update(['status' => MinecraftServerStatus::Stopped->value]);
        $rcon = $this->createMock(MinecraftRconService::class);
        $rcon->expects($this->never())->method('execute');

        $this->expectException(MinecraftServerStateException::class);

        (new SendMinecraftServerCommandAction($rcon))->execute($server, 'list');
    }

    public function test_execute_propagates_rcon_exception_for_the_controller_to_handle(): void
    {
        $owner = User::factory()->create();
        $server = MinecraftServer::factory()->for($owner, 'owner')->create([
            'status' => MinecraftServerStatus::Running,
        ]);
        $rcon = $this->createMock(MinecraftRconService::class);
        $rcon->expects($this->once())
            ->method('execute')
            ->with("minecraft-{$server->id}-console-service", 'list')
            ->willThrowException(new RuntimeException('Connection refused'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Connection refused');

        (new SendMinecraftServerCommandAction($rcon))->execute($server, 'list');
    }
}
