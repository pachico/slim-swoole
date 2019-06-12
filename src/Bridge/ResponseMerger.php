<?php

namespace Pachico\SlimSwoole\Bridge;

use Slim\App;
use Psr\Http\Message\ResponseInterface;
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
     * @param Response $response
     * @param swoole_http_response $swooleResponse
     *
     * @return swoole_http_response
     */
    public function mergeToSwoole(
        ResponseInterface $response,
        swoole_http_response $swooleResponse
    ): swoole_http_response {
        $container = $this->app->getContainer();

        $settings = $container->get('settings');
        if (isset($settings['addContentLengthHeader']) && $settings['addContentLengthHeader'] == true) {
            $size = $response->getBody()->getSize();
            if ($size !== null) {
                $swooleResponse->header('Content-Length', (string) $size);
            }
        }

        if (!empty($response->getHeaders())) {
            foreach ($response->getHeaders() as $key => $headerArray) {
                $swooleResponse->header($key, implode('; ', $headerArray));
            }
        }

        $swooleResponse->status($response->getStatusCode());

        if ($response->getBody()->getSize() > 0) {
            if ($response->getBody()->isSeekable()) {
                $response->getBody()->rewind();
            }

            $swooleResponse->write($response->getBody()->getContents());
        }

        return $swooleResponse;
    }
}
