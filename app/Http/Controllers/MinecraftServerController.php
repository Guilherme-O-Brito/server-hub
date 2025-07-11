<?php

namespace App\Http\Controllers;

use App\Events\ConsoleOutput;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Process;

class MinecraftServerController extends Controller
{
    public function start()
    {   
        if (auth()->user()->isAdmin()) {
            
            
            shell_exec('/home/guilherme/Documents/minecraft-server/start.sh 2>&1');
            #$terminal = shell_exec('tmux new-session -s minecraft /home/guilherme/Documents/minecraft-server/start.sh > /opt/tmux_sockets/server.log 2>&1');
            #$terminal = shell_exec('nohup bash home/guilherme/Documents/minecraft-server/start.sh > /opt/tmux_sockets/server.log 2>&1 &');
            $logPath = storage_path('logs/minecraft.log');
            shell_exec('tmux -S /var/tmux_socket/sessions pipe-pane -o -t minecraft "cat >> '. $logPath .'"');

            $path = base_path();
            // inicia o monitor de console tmux para ter acesso as mensagens recebidas nos consoles dos jogos
            $process = Process::path($path)->start('php artisan tmux:monitor 2>&1 &');

            return response()->json(['error' => 'Iniciando Servidor!'], 200);
        } else {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

    }

    public function stop()
    {
        if (auth()->user()->isAdmin()) {
            $logPath = storage_path('logs/minecraft.log');
            // encerra o processo do tmux monitor
            shell_exec('tmux -S /var/tmux_socket/sessions pipe-pane -t minecraft');
            shell_exec('pkill -f "artisan tmux:monitor"');
            // encerra o servidor do minecraft
            shell_exec('/home/guilherme/Documents/minecraft-server/stop.sh 2>&1');
            // limpa o cache de mensagens do console
            Redis::del('console:log');
            shell_exec('> '.$logPath);
            return response()->json(['error' => 'Finalizando Servidor'], 200);
        } else {
            return response()->json(['error' => 'Acesso negado'], 403);
        }
    }

    public function sendCommand(Request $request) {

        $request->validate([
            'command' => ['required']
        ]);

        // comandos proibidos de serem usados no console
        $bannedCommands = [
            'stop',
            'reload',
            'save-off'
        ];
        // comandos que precisam de algum tipo de permissão
        $protectedCommands = [
            'op',
            'deop',
            'ban',
            'pardon',
            'kick',
            'difficulty',
            'whitelist',
            'gamerule',
        ];

        $command = $request->input('command');

        if (in_array($command, $bannedCommands)) {
            broadcast(new ConsoleOutput('Este é um comando proibido!'));
            return response(status: 200);
        }

        if ($command == 'stop') {
            broadcast(new ConsoleOutput('o comando stop não é permitido encerre o servidor de forma correta'));
            return response(status: 200);
        }

        if (preg_match('/@(?:e|a|p)\b/', $command)) {
            broadcast(new ConsoleOutput('Não são permitidos comandos para multiplas entidades usando @e, @a e @p!'));
            return response(status: 200);
        }

        $output = '['.Carbon::now()->toTimeString().' '.auth()->user()->name.']: '.$command;
        cache_console_log($output, "console:log");
        broadcast(new ConsoleOutput($output));

        shell_exec('tmux -S /var/tmux_socket/sessions send -t minecraft "'.$command.'" ENTER');
        
        return response(status: 200);

    }

}
