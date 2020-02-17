<?php
use Spiral\Goridge;
use Spiral\RoadRunner;
use RoadRunnerUbiquity\Request;

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', __DIR__.DS);
$config=include_once ROOT.'config/config.php';

ini_set('display_errors', 'stderr');
require_once ROOT.'./../vendor/autoload.php';
require_once ROOT.'config/services.php';

$in = defined('STDIN') ? STDIN : fopen("php://stdin","r");
$out = defined('STDOUT') ? STDOUT : fopen('php://stdout', 'w');

$worker = new RoadRunner\Worker(new Goridge\StreamRelay($in, $out));
$request = new Request($worker);

\Ubiquity\controllers\Startup::init($config);

while ($request->acceptRequest()) {

    $uri = \ltrim(\urldecode(\parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)),'/');
    if ($uri!=='favicon.ico' && ($uri==null || !\file_exists(__DIR__ . '/../' .$uri))) {
        $_GET['c'] = $uri;
    }else{
        $_GET['c']='';
    }

    $start = microtime(true);
    \Ubiquity\controllers\Startup::forward($_GET['c']);
    $duration = (microtime(true) - $start) * 1000;
    file_put_contents("/tmp/rr.txt",$duration);

    $request->sendResponse();
}
