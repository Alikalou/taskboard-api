<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Http.php';
require_once __DIR__ . '/../src/Router.php';
require_once __DIR__ . '/../src/TasksController.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/TasksRepository.php';

use Taskboard\Http;
use Taskboard\Router;
use Taskboard\TasksController;
use Taskboard\Database;
use Taskboard\TasksRepository;

// 1) Bootstrap: schema + PDO (idempotent)
Database::runSchema(__DIR__ . '/../storage/schema.sql');

// 2) Global headers for a JSON API
Http::cors();              // add CORS headers
Http::forceJsonResponse(); // Content-Type: application/json

// 2a) Short-circuit CORS preflight
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    // Add any extra allow headers if your frontend needs them
    http_response_code(204);
    exit;
}

// 3) Build dependencies
$router = new Router();
$repo   = new TasksRepository(Database::conn());
$tasks  = new TasksController($repo);

// 4) Routes (note: second arg is a PHP callable; [$instance, 'method'] is valid)
$router->get('/v1/tasks',          [$tasks, 'index']);   // list
$router->get('/v1/tasks/{id}',     [$tasks, 'show']);    // read one
$router->post('/v1/tasks',         [$tasks, 'store']);   // create
$router->put('/v1/tasks/{id}',     [$tasks, 'update']);  // update
$router->delete('/v1/tasks/{id}',  [$tasks, 'destroy']); // delete
$router->get('/v1/health',         function () { Http::json(['status' => 'ok'], 200); });

// 5) Dispatch: make sure to pass only the path, not the full URI with query
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = $_SERVER['REQUEST_URI'] ?? '/';
$path   = parse_url($uri, PHP_URL_PATH) ?: '/';

$router->dispatch($method, $path);
