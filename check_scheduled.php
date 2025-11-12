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
    'username' => 'root',
    'password' => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

$capsule->setEventDispatcher(new Dispatcher($container));
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Check scheduled deployments
$scheduledDeployments = \App\Models\ScheduledDeployment::where('status', 'pending')->get();

echo "Pending scheduled deployments: " . $scheduledDeployments->count() . "\n";

foreach ($scheduledDeployments as $scheduled) {
    echo "ID: " . $scheduled->id . "\n";
    echo "Scheduled at: " . $scheduled->scheduled_at . "\n";
    echo "Now: " . Date::now() . "\n";
    echo "Due: " . ($scheduled->scheduled_at <= Date::now() ? 'Yes' : 'No') . "\n";
    echo "Project: " . $scheduled->project->name . "\n";
    echo "---\n";
}