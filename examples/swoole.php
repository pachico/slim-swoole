<?php

use Pachico\SlimSwoole\BridgeManager;

require __DIR__ . '/slim.php';

/* @var $app Slim\App */
$bridgeManager = new BridgeManager($app);

/**
 * Start the Swoole server
 */
$http = new swoole_http_server("0.0.0.0", 8081);

/**
 * Register the on "start" event
 */
$http->on("start", function (\swoole_http_server $server) {
    echo sprintf('Swoole http server is started at http://%s:%s', $server->host, $server->port), PHP_EOL;
});

/**
 * Register the on "request event
 */
$http->on(
    "request",
    function (swoole_http_request $swooleRequest, swoole_http_response $swooleResponse) use ($bridgeManager) {
        $bridgeManager->process($swooleRequest, $swooleResponse)->end();
    }
);

$http->start();
