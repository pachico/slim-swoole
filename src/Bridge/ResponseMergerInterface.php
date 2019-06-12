<?php

namespace Pachico\SlimSwoole\Bridge;

use Psr\Http\Message\ResponseInterface;
use swoole_http_response;

interface ResponseMergerInterface
{
    /**
     * @param ResponseInterface $response
     * @param swoole_http_response $swooleResponse
     *
     * @return swoole_http_response
     */
    public function mergeToSwoole(
        ResponseInterface $response,
        swoole_http_response $swooleResponse
    ): swoole_http_response;
}
