<?php

namespace Pachico\SlimSwoole\Bridge;

use Slim\Http;

interface ResponseMergerInterface
{

    /**
     * @param Http\Response $slimResponse
     * @param \swoole_http_response $swooleResponse
     *
     * @return \swoole_http_response
     */
    public function mergeToSwoole(Http\Response $slimResponse, \swoole_http_response $swooleResponse);
}
