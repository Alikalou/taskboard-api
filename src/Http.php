<?php
declare(strict_types=1);

namespace Taskboard;

final class Http
{
    /**
     * CORS for every request. Exposes pagination headers by default.
     * Call once at the start of public/index.php (before any output).
     */
    public static function cors(
        array $exposed = ['X-Total-Count','X-Limit','X-Offset','X-Has-More']
    ): void {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET,POST,PATCH,PUT,DELETE,OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, If-Match, If-None-Match');
        if ($exposed) {
            header('Access-Control-Expose-Headers: ' . implode(', ', $exposed));
        }
        header('Vary: Origin');

        // Preflight short-circuit
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    /** Force JSON content-type for responses you build manually. */
    public static function forceJsonResponse(): void
    {
        header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * Send a JSON response with optional extra headers.
     * NOTE: $data is `mixed` so you can send arrays OR scalars if needed.
     */
    public static function json(mixed $data, int $status = 200, array $extraHeaders = []): void
    {
        http_response_code($status);
        foreach ($extraHeaders as $k => $v) {
            header($k . ': ' . $v);
        }
        // Ensure content-type if caller forgot to set it earlier
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** Standard error envelope using json(). */
    public static function error(string $code, string $message, int $status = 400, array $fields = []): void
    {
        self::json([
            'error'   => $code,
            'message' => $message,
            'fields'  => $fields ?: (object)[],
        ], $status);
    }

    // -------------------------
    // Query param helpers
    // -------------------------

    /** Get a string query param (trimmed) or default/null. */
    public static function getQueryParam(string $key, ?string $default = null): ?string
    {
        return isset($_GET[$key]) ? trim((string) $_GET[$key]) : $default;
    }

    /** Get a validated int query param or default/null. */
    public static function getQueryInt(string $key, ?int $default = null): ?int
    {
        if (!isset($_GET[$key])) {
            return $default;
        }
        $v = filter_var($_GET[$key], FILTER_VALIDATE_INT);
        return $v === false ? $default : $v;
    }

    /** Get a true/false query param (accepts 1/0, true/false, yes/no), or default/null. */
    public static function getQueryBool(string $key, ?bool $default = null): ?bool
    {
        if (!isset($_GET[$key])) {
            return $default;
        }
        $v = filter_var($_GET[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $v === null ? $default : $v;
    }

    /**
     * Get an enum-like query param restricted to allowed values.
     * Returns the matched value or default/null if not allowed.
     */
    public static function getQueryEnum(string $key, array $allowed, ?string $default = null): ?string
    {
        $val = self::getQueryParam($key, null);
        return ($val !== null && in_array($val, $allowed, true)) ? $val : $default;
    }

    // -------------------------
    // Small convenience
    // -------------------------

    /** Add a bunch of headers at once (used by controllers). */
    public static function addHeaders(array $headers): void
    {
        foreach ($headers as $k => $v) {
            header($k . ': ' . $v);
        }
    }
}
