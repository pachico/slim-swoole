<?php

namespace Pachico\SlimSwoole;

use Pachico\SlimSwoole\Bridge;
use Slim\App;
use Slim\Http;
use swoole_http_request;
use swoole_http_response;

class BridgeManager implements BridgeManagerInterface
{
    /**
     * @var App
     */
    private $app;

    /**
     * @var Bridge\RequestTransformerInterface
     */
    private $requestTransformer;

    /**
     * @var Bridge\ResponseMergerInterface
     */
    private $responseMerger;

    /**
     * @param App $app
     * @param Bridge\RequestTransformerInterface $requestTransformer
     * @param Bridge\ResponseMergerInterface $responseMerger
     */
    public function __construct(
        App $app,
        Bridge\RequestTransformerInterface $requestTransformer = null,
        Bridge\ResponseMergerInterface $responseMerger = null
    ) {
        $this->app = $app;
        $this->requestTransformer = $requestTransformer ?: new Bridge\RequestTransformer();
        $this->responseMerger = $responseMerger ?: new Bridge\ResponseMerger($this->app);
    }

    /**
     * @param swoole_http_request $swooleRequest
     * @param swoole_http_response $swooleResponse
     *
     * @return swoole_http_response
     */
    public function process(
        swoole_http_request $swooleRequest,
        swoole_http_response $swooleResponse
    ): swoole_http_response {
        $slimRequest = $this->requestTransformer->toSlim($swooleRequest);
        $slimResponse = $this->app->process($slimRequest, new Http\Response());

        return $this->responseMerger->mergeToSwoole($slimResponse, $swooleResponse);
    }
}
