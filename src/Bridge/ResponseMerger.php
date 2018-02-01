<?php

namespace Pachico\SlimSwoole\Bridge;

use Slim\Http;
use Slim\App;

class ResponseMerger implements ResponseMergerInterface
{

    /**
     * @var App
     */
    private $app;

    /**
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * @param Http\Response $slimResponse
     * @param \swoole_http_response $swooleResponse
     *
     * @return \swoole_http_response
     */
    public function mergeToSwoole(Http\Response $slimResponse, \swoole_http_response $swooleResponse)
    {
        $container = $this->app->getContainer();

        if (isset($container->get('settings')['addContentLengthHeader']) &&
            $container->get('settings')['addContentLengthHeader'] == true) {
            $size = $slimResponse->getBody()->getSize();
            if ($size !== null) {
                $swooleResponse->header('Content-Length', (string) $size);
            }
        }

        foreach ($slimResponse->getHeaders() as $key => $headerArray) {
            $swooleResponse->header($key, implode('; ', $headerArray));
        }

        $swooleResponse->status($slimResponse->getStatusCode());

        if ($slimResponse->getBody()->getSize() > 0) {
            if ($slimResponse->getBody()->isSeekable()) {
                $slimResponse->getBody()->rewind();
            }

            $swooleResponse->write((string) $slimResponse->getBody());
        }

        return $swooleResponse;
    }
}
