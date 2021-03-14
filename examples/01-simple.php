<?php

use Pachico\SlimSwoole\BridgeManager;
use Slim\Http;

require __DIR__ . '/../vendor/autoload.php';

/**
 * This is how you would normally bootstrap your Slim application
 * For the sake of demonstration, we also add a simple middleware
 * to check that the entire app stack is being setup and executed
 * properly.
 */
$app = new \Slim\App();
$app->any('/foo[/{myArg}]', function (Http\Request $request, Http\Response $response, array $args) {

    $data = [
        'args' => $args,
        'request_uri' => $request->getUri(),
        'request_body' => (string) $request->getBody(),
        'request_parsed_body' => $request->getParsedBody(),
        'request_params' => $request->getParams(),
        'request_headers' => $request->getHeaders(),
        'request_uploadedFiles' => $request->getUploadedFiles()
    ];

    return $response->withJson($data, 200, JSON_PRETTY_PRINT);
})->add(function (Http\Request $request, Http\Response $response, callable $next) {
    // No-OP middleware. Just for demonstration purposes
    
    // echo "before app stack\n";
    $response = $next($request, $response);
    // echo "after app stack\n";
    return $response;
});

/**
 * We instanciate the BridgeManager (this library)
 */
$bridgeManager = new BridgeManager($app);

/**
 * We start the Swoole server
 */
$http = new swoole_http_server("0.0.0.0", 8081);

/**
 * We register the on "start" event
 */
$http->on("start", function (\swoole_http_server $server) {
    echo sprintf('Swoole http server is started at http://%s:%s', $server->host, $server->port), PHP_EOL;
});

/**
 * We register the on "request event, which will use the BridgeManager to transform request, process it
 * as a Slim request and merge back the response
 *
 */
$http->on(
    "request",
    function (swoole_http_request $swooleRequest, swoole_http_response $swooleResponse) use ($bridgeManager) {
        $bridgeManager->process($swooleRequest, $swooleResponse)->end();
    }
);

$http->start();
