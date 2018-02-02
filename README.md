# slim-swoole

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/pachico/slim-swoole/badges/quality-score.png?b=0.x-dev)](https://scrutinizer-ci.com/g/pachico/slim-swoole/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/pachico/slim-swoole/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/pachico/slim-swoole/?branch=master)
[![Build Status](https://travis-ci.org/pachico/slim-swoole.svg?branch=master)](https://travis-ci.org/pachico/slim-swoole)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)


This is a brige library to run [Slim framework](https://www.slimframework.com/) Slim framework applications using [Swoole engine](https://www.swoole.co.uk/).

It is still in development so any contribution and test will be more than welcome.

## Install

Via Composer

``` bash
$ composer require pachico/slim-swoole
```

## Usage

``` php
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
        'body' => (string) $request->getBody(),
        'parsedBody' => $request->getParsedBody(),
        'params' => $request->getParams(),
        'headers' => $request->getHeaders(),
        'uploadedFiles' => $request->getUploadedFiles()
    ];

    return $response->withJson($data);
})->add(function (Http\Request $request, Http\Response $response, callable $next) {

    $response->getBody()->write('BEFORE' . PHP_EOL);
    $response = $next($request, $response);
    $response->getBody()->write(PHP_EOL . 'AFTER');

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


```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email pachicodev@gmail.com instead of using the issue tracker.

## Credits


- [Mariano F.co Benítez Mulet](https://github.com/pachico)
- [All Contributors](https://github.com/pachico/slim-swoole/graphs/contributors) 

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

