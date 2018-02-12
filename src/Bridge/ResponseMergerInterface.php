<?php

namespace Pachico\SlimSwoole\Bridge;

use Slim\Http;
use swoole_http_response;

interface ResponseMergerInterface
{
    /**
     * @param Http\Response $slimResponse
     * @param swoole_http_response $swooleResponse
     *
     * @return swoole_http_response
     */
    public function mergeToSwoole(
        Http\Response $slimResponse,
        swoole_http_response $swooleResponse
    ): swoole_http_response;
}
