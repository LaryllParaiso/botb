<?php
/**
 * WebSocketService — sends notifications to the Node.js WebSocket server
 * via its internal HTTP endpoint.
 *
 * Usage:
 *   WebSocketService::notify('band_change', ['band_id' => 1, 'band' => $bandData]);
 *   WebSocketService::notify('scores_submitted', ['band_id' => 1, 'judge_id' => 2]);
 *   WebSocketService::notify('admin_update', ['type' => 'band_crud']);
 */
class WebSocketService
{
    private static $httpUrl = 'http://127.0.0.1:8082/notify';
    private static $timeout = 1; // seconds — keep it fast, non-blocking feel

    /**
     * Send a notification to the WebSocket server.
     * Fails silently if the WS server is not running.
     *
     * @param string $event  Event name
     * @param array  $data   Event payload
     * @return bool
     */
    public static function notify(string $event, array $data = []): bool
    {
        $payload = json_encode([
            'event' => $event,
            'data'  => $data
        ]);

        $ch = curl_init(self::$httpUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::$timeout,
            CURLOPT_CONNECTTIMEOUT => self::$timeout,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            // WS server not running — fail silently, system still works via fallback polling
            error_log("[WebSocketService] Could not reach WS server: {$error}");
            return false;
        }

        return $httpCode === 200;
    }
}
