<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$router = $app->make('router');

$route = $router->getRoutes()->getByName('scheduled-deployments.index');

if ($route) {
    echo "Route found: " . $route->uri() . "\n";
} else {
    echo "Route not found\n";
}
