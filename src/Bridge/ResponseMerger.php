<?php

namespace Pachico\SlimSwoole\Bridge;

use Slim\App;
use Slim\Http;
use swoole_http_response;

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
     * @param swoole_http_response $swooleResponse
     *
     * @return swoole_http_response
     */
    public function mergeToSwoole(
        Http\Response $slimResponse,
        swoole_http_response $swooleResponse
    ): swoole_http_response {
        $container = $this->app->getContainer();

        $settings = $container->get('settings');
        if (isset($settings['addContentLengthHeader']) && $settings['addContentLengthHeader'] == true) {
            $size = $slimResponse->getBody()->getSize();
            if ($size !== null) {
                $swooleResponse->header('Content-Length', (string) $size);
            }
        }

        if (!empty($slimResponse->getHeaders())) {
            foreach ($slimResponse->getHeaders() as $key => $headerArray) {
                $swooleResponse->header($key, implode('; ', $headerArray));
            }
        }

        $swooleResponse->status($slimResponse->getStatusCode());

        if ($slimResponse->getBody()->getSize() > 0) {
            if ($slimResponse->getBody()->isSeekable()) {
                $slimResponse->getBody()->rewind();
            }

            $swooleResponse->write($slimResponse->getBody()->getContents());
        }

        return $swooleResponse;
    }
}
