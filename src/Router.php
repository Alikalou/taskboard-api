<?php
namespace Taskboard;

final class Router
{
    private array $routes = [
        'GET' => [], 'POST' => [], 'PATCH' => [], 'PUT' => [], 'DELETE' => []
    ];
    //An associative array, where the keys are the HTTP methods,
    // and the values are another associative array, with keys being paths, and values being handlers.

    public function get(string $path, callable $handler): void  { $this->routes['GET'][$path] = $handler; }
    public function post(string $path, callable $handler): void { $this->routes['POST'][$path] = $handler; }
    public function patch(string $path, callable $handler): void{ $this->routes['PATCH'][$path] = $handler; }
    public function put(string $path, callable $handler): void  { $this->routes['PUT'][$path] = $handler; }
    public function delete(string $path, callable $handler): void{ $this->routes['DELETE'][$path] = $handler; }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            Http::error('not_found', 'No route for ' . $method . ' ' . $path, 404);
            return;
        }
        $handler();
    }
}

//لا اله الا الله, سبحانك اني كنت من الظالمين
