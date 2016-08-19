<?php

use workerman\Worker;

require_once APP_PATH . "/library/workerman/Autoloader.php";

$http_worker = new Worker('http://0.0.0.0:8888');
$http_worker->count = 4;
$http_worker->onMessage = function($connection, $data) {
    $connection->send('hello world');
};

Worker::runAll();
