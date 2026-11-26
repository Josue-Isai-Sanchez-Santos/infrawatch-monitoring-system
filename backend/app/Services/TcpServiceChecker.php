<?php

namespace App\Services;

class TcpServiceChecker
{
    /**
     * @return array{reachable: bool, response_time_ms: int, error: string}
     */
    public function check(string $ip, int $port): array
    {
        $errorCode = 0;
        $errorMessage = '';
        $startedAt = microtime(true);

        $connection = @fsockopen($ip, $port, $errorCode, $errorMessage, 3);

        $responseTimeMs = (int) round((microtime(true) - $startedAt) * 1000);

        if ($connection !== false) {
            fclose($connection);

            return [
                'reachable' => true,
                'response_time_ms' => $responseTimeMs,
                'error' => '',
            ];
        }

        return [
            'reachable' => false,
            'response_time_ms' => $responseTimeMs,
            'error' => $errorMessage ?: "TCP connection failed ({$errorCode}).",
        ];
    }
}
