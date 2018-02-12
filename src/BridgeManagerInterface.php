<?php

namespace Pachico\SlimSwoole;

use swoole_http_request;
use swoole_http_response;

interface BridgeManagerInterface
{
    /**
     * @param swoole_http_request $swooleRequest
     * @param swoole_http_response $swooleResponse
     *
     * @return swoole_http_response
     */
    public function process(
        swoole_http_request $swooleRequest,
        swoole_http_response $swooleResponse
    ): swoole_http_response;
}
