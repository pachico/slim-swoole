<?php

use Slim\Http;

require __DIR__ . '/../vendor/autoload.php';

/**
 * This is how you would normally bootstrap your Slim application
 * For the sake of demonstration, I also added a silly Middleware
 * to check that the entire app stack is being setup correctly.
 */
$app = new \Slim\App();
$app->any('/foo[/{myArg}]', function (Http\Request $request, Http\Response $response, array $args) {

    $data = [
        'args' => $args,
        'body' => (string) $request->getBody(),
        'parsedBody' => (string) $request->getParsedBody(),
        'params' => $request->getParams(),
        'headers' => $request->getHeaders()
    ];

    return $response->withJson($data);
})->add(function (Http\Request $request, Http\Response $response, callable $next) {

    $response->getBody()->write('BEFORE' . PHP_EOL);
    $response = $next($request, $response);
    $response->getBody()->write(PHP_EOL . 'AFTER');

    return $response;
});
