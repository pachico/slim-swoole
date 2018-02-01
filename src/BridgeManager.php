<?php

namespace Pachico\SlimSwoole;

use Pachico\SlimSwoole\Bridge;
use Slim\App;
use Slim\Http;

class BridgeManager implements BridgeManagerInterface
{

    /**
     * @var App
     */
    private $app;

    /**
     * @var Bridge\RequestTransformer
     */
    private $requestTransformer;

    /**
     * @var Bridge\ResponseMerger
     */
    private $responseMerger;

    /**
     * @param App $app
     * @param Bridge\RequestTransformer $requestTransformer
     * @param Bridge\ResponseMerger $responseMerger
     */
    public function __construct(
        App $app,
        Bridge\RequestTransformer $requestTransformer = null,
        Bridge\ResponseMerger $responseMerger = null
    ) {
        $this->app = $app;
        $this->requestTransformer = $requestTransformer ?: new Bridge\RequestTransformer();
        $this->responseMerger = $responseMerger ?: new Bridge\ResponseMerger($this->app);
    }

    /**
     * @param \swoole_http_request $swooleRequest
     * @param \swoole_http_response $swooleResponse
     *
     * @return \swoole_http_response
     */
    public function process(\swoole_http_request $swooleRequest, \swoole_http_response $swooleResponse)
    {
        $slimRequest = $this->requestTransformer->toSlim($swooleRequest);
        $slimResponse = $this->app->process($slimRequest, new Http\Response());
        $swooleResponse = $this->responseMerger->mergeToSwoole($slimResponse, $swooleResponse);

        return $swooleResponse;
    }
}
