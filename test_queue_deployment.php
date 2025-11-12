<?php

require_once 'vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Date;
use App\Models\ScheduledDeployment;
use App\Jobs\ProcessScheduledDeployment;

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

// Create a test scheduled deployment that is due now
$scheduled = new ScheduledDeployment();
$scheduled->project_id = 1;
$scheduled->user_id = 1;
$scheduled->scheduled_at = Date::now()->subMinutes(5);
$scheduled->description = 'Test queue deployment';
$scheduled->save();

echo "Test scheduled deployment created with ID: " . $scheduled->id . "\n";

// Dispatch the job to the queue
$job = ProcessScheduledDeployment::dispatch($scheduled);

echo "Job dispatched to queue with ID: " . $job->getJobId() . "\n";
echo "Run 'php artisan queue:work' to process the job.\n";