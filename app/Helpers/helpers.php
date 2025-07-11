<?php 

use Illuminate\Support\Facades\Redis;

if (!function_exists('cache_console_log')) {
    function cache_console_log(string $line, string $key)
    {
        Redis::lpush($key, $line);
        Redis::ltrim($key, 0, 199); // garante que o historico de mensagens tenha apenas 200 mensagens armazenadas
    }
}