<?php

namespace App\Support;

final class AgentDebugLog
{
    private const REL_PATH = '.cursor/debug-8193d6.log';

    /**
     * @param  array<string, mixed>  $data
     */
    public static function line(string $hypothesisId, string $location, string $message, array $data = [], string $runId = 'pre-fix'): void
    {
        $path = base_path(self::REL_PATH);
        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $payload = [
            'sessionId' => '8193d6',
            'runId' => $runId,
            'hypothesisId' => $hypothesisId,
            'location' => $location,
            'message' => $message,
            'data' => $data,
            'timestamp' => (int) round(microtime(true) * 1000),
        ];

        @file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND | LOCK_EX);
    }
}
