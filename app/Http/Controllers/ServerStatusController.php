<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ServerStatusController extends Controller
{
    public function status() {

        $cpuUsage = round(sys_getloadavg()[0], 2); // media de uso do cpu no ultimo minuto
        $memoryUsage = $this->getUsedMemory(); // total de memoria usada pelo sistema
        $totalMemory = $this->getTotalMemory();

        return response()->json([
            'cpu_usage' => $cpuUsage,
            'memory_usage' => $memoryUsage,
            'total_memory' => $totalMemory
        ]);

    }

    private function getTotalMemory(){
        $meminfo = file_get_contents("/proc/meminfo");
        if (preg_match("/MemTotal:\s+(\d+) kB/", $meminfo, $matches)) {
            $memoryInKB = (int)$matches[1];
            $memoryInGB = $memoryInKB / 1024 / 1024;
            return round($memoryInGB, 1);
        }
        return null;
    }

    private function getUsedMemory()
    {
        $meminfo = file_get_contents("/proc/meminfo");

        $data = [];
        foreach (explode("\n", $meminfo) as $line) {
            if (preg_match("/^(\w+):\s+(\d+)\s+kB$/", $line, $matches)) {
                $data[$matches[1]] = (int) $matches[2];
            }
        }

        if (isset($data['MemTotal']) && isset($data['MemFree']) && isset($data['Buffers']) && isset($data['Cached'])) {
            $totalMemory = $data['MemTotal'];
            $freeMemory = $data['MemFree'] + $data['Buffers'] + $data['Cached'];
            $usedMemory = $totalMemory - $freeMemory;

            $usedMemoryInGB = $usedMemory / 1024 / 1024;
            return round($usedMemoryInGB, 1);
        }

        return null;
    }

}
