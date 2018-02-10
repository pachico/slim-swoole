<?php

namespace Pachico\SlimSwoole;

interface BridgeManagerInterface
{
    /**
     * @param \swoole_http_request $swooleRequest
     * @param \swoole_http_response $swooleResponse
     *
     * @return \swoole_http_response
     */
    public function process(
        \swoole_http_request $swooleRequest,
        \swoole_http_response $swooleResponse
    ): \swoole_http_response;
}
