<?php
declare(strict_types=1);
//Strict typing, if passed with the wrong type php will throw an error.


require __DIR__ . '/../src/Http.php';
require __DIR__ . '/../src/Router.php';
require __DIR__ . '/../src/TasksController.php';
//These are the include C-like statements that bring in the files we need to run the code.


use Taskboard\Http;
use Taskboard\Router;
use Taskboard\TasksController;
//Name spaces, which I don't know much about.


// Basic headers for JSON APIs
Http::cors();                    // Allow cross-origin for local dev
Http::forceJsonResponse();       // Content-Type: application/json
//Here, we are forcing the response to follow the JSON format.
// cors() is not well understood yet.

// Instantiate router and register routes
$router = new Router();
$tasks  = new TasksController();
//This is OOP, Router() is a class, and we are creating an instance of it called $router.
//Of course, this standard class has a definition, and below you can see that we are accessing the method ...
//get() of the class Router(), the method takes two parameters, the path and to dispatch to the proper task controller.



// v1 routes
$router->get('/v1/tasks',        [$tasks, 'index']);   // list tasks
$router->get('/v1/health',       fn() => Http::json(['ok' => true]));
//The arrow here is just the dot operator in OOP
//So, we are registering the routes here, two routes are available in the program,
//These are the v1/tasks and v1/health routes.
//but what is really the second argument in the get() method?
//The first one is a callable, which is an array with two elements, the first is the instance of the class TasksController() and the second is the method index() of that class.


// Dispatch
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');

//Two things to know here, registering routes and dispatching them (sending them to the proper controller).
//Both get, and dispatch in the methods to apply the two principles above.
//