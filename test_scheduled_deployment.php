<?php

require_once 'vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Date;

// Create a service container
$container = new Container;

// Create a database capsule
$capsule = new Capsule($container);
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'deployment_management',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

$capsule->setEventDispatcher(new Dispatcher($container));
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Create a test scheduled deployment
$scheduled = new \App\Models\ScheduledDeployment();
$scheduled->project_id = 1;
$scheduled->user_id = 1;
$scheduled->scheduled_at = Date::now()->addMinutes(5);
$scheduled->description = 'Test scheduled deployment';
$scheduled->save();

echo "Test scheduled deployment created successfully!\n";