<?php

namespace Pachico\SlimSwoole\Bridge;

use Slim\Http;
use swoole_http_request;

interface RequestTransformerInterface
{
    /**
     * @param swoole_http_request $request
     *
     * @return Http\Request
     *
     */
    public function toSlim(swoole_http_request $request): Http\Request;
}
