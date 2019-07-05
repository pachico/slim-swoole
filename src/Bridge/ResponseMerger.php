<?php

namespace Pachico\SlimSwoole\Bridge;

use Slim\App;
use Psr\Http\Message\ResponseInterface;
use swoole_http_response;
use Dflydev\FigCookies\SetCookies;

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
            $this->setCookies($swooleResponse, $response);

            $response = $response->withoutHeader('Set-Cookie');

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

    private function setCookies($swooleResponse, $response)
    {
        if (!$response->hasHeader('Set-Cookie')) {
            return;
        }

        $setCookies = SetCookies::fromSetCookieStrings($response->getHeader('Set-Cookie'));
        foreach ($setCookies->getAll() as $setCookie) {
            $swooleResponse->cookie(
                $setCookie->getName(),
                $setCookie->getValue(),
                $setCookie->getExpires(),
                $setCookie->getPath(),
                $setCookie->getDomain(),
                $setCookie->getSecure(),
                $setCookie->getHttpOnly()
            );
        }
    }
}
