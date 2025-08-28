<?php
namespace Taskboard;

final class Http
{
    public static function cors(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET,POST,PATCH,PUT,DELETE,OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, If-Match, If-None-Match');
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
    /*
    The above acrynom CORS stands for Cross-Origin Resource Sharing.
    Noting is returned from the function, 
    A built-in php function header() is used to send raw HTTP headers to a client.
    The first optional header takes a string that basically allow requests from any origin, the asterisk means 'any'
    The second header allows the receiver to recieve all http methods.
    The third header addes an authorization header, content-type header, and two other headers.
    What you need to remember here is that it is the communication that is done in a typical web service.
    So, in a client server model, allowing a request from outside the set up connection is a security risk.
    */

    
    


    public static function forceJsonResponse(): void
    {
        header('Content-Type: application/json; charset=utf-8');
    }
    //Forcing a specific json format

    public static function json(array $data, int $status = 200, array $extraHeaders = []): void
    {
        http_response_code($status);
        foreach ($extraHeaders as $k => $v) {
            header($k . ': ' . $v);
        }
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    // Helper for sending JSON responses in a consistent way.
    // 1. Sets the HTTP status code (default 200 OK).
    // 2. Applies any extra headers passed in (e.g., X-Total-Count, ETag).
    // 3. Encodes the given PHP array into JSON and outputs it.
    // Centralizing this means every controller response is uniform,
    // and we don't repeat http_response_code(), header(), and json_encode()
    // all over the codebase.


    public static function error(string $code, string $message, int $status = 400, array $fields = []): void
    {
        self::json([
            'error' => $code,
            'message' => $message,
            'fields' => $fields ?: (object)[],
        ], $status);
    }

    //This is a specific json response for errors, it uses the json() method above to send the error response.
}
