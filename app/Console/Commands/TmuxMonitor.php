<?php

namespace App\Console\Commands;

use App\Events\ConsoleOutput;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\Process\Process;

class TmuxMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmux:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitora as mensagens vindas dos servidores que rodam em tmux e gerencia um cache delas';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        
        $this->info('Iniciando serviço de coleta de mensagens do console . . .');

        $logPath = storage_path('logs/minecraft.log');

        // inicia o processo que monitora o arquivo de log vindo dos consoles dos servidores
        $process = new Process(['tail', '-F', $logPath]);
        $process->setTimeout(null);
        $process->start();

        foreach ($process as $type => $line) {
            $line = trim($line);
            if (!empty($line) && preg_match('/^\[[^\]]*\]:/', $line)) {
                $line = $this->sanitizeMessage($line);
                // Armazena cache das mensagens no redis para consulta de mensagens mais antigas
                cache_console_log($line, "console:log");
                
                // broadcaste para os usuarios conectados 
                broadcast(new ConsoleOutput($line));
            }
        }

    }

    private function sanitizeMessage(string $rawMessage): string {
        
        // Remove códigos ANSI
        $message = preg_replace('/\x1B(?:[@-Z\\-_]|\[[0-?]*[ -\/]*[@-~])/', '', $rawMessage);

        // Remove "> " do início de cada linha
        $message = preg_replace('/^> ?/m', '', $message);

        // Remove caracteres de controle ASCII
        $message = preg_replace('/[\x1F\x7F]/', '', $message);

        //$message = preg_replace('/\e\[[0-9;]*[a-zA-Z]/', '', $message);

        //$message = preg_replace('#\\x1b[[][^A-Za-z]*[A-Za-z]#', '', $rawMessage);

        return $message;
    }

}
