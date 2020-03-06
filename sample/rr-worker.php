<?php

use RoadRunnerUbiquity\Request;
use Ubiquity\controllers\Startup;

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', __DIR__ . DS . '..' . DS . 'app' . DS);

ini_set('display_errors', 'stderr');
ini_set('max_execution_time', 0);

$config=include_once ROOT . 'config/config.php';
$sConfig= include __DIR__.\DS.'config.php';
$config ["siteUrl"] = 'http://'.$sConfig['host'].':'.$sConfig['port'].'/';
$config ['sessionName'] = $sConfig['sessionName'];

require_once ROOT.'../vendor/autoload.php';
require_once ROOT.'config/services.php';

$request = new Request($config);

Startup::init($config);

while ($request->acceptRequest()) {
    Startup::forward(
        $request->ubiquityRoute()
    );
    $request->sendResponse()->garbageCollect();
}
