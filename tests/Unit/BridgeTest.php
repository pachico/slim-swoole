<?php

namespace Pachico\SlimSwooleUnitTest;

use Pachico\SlimSwoole\BridgeManager;
use Pachico\SlimSwoole\Bridge;

class BridgeTest extends AbstractTestCase
{

    private $app;
    private $swooleRequest;
    private $swooleResponse;
    private $requestTransformer;
    private $responseMerger;

    public function setUp(): void
    {
        parent::setUp();

        $this->app = $this->getMockBuilder(\Slim\App::class)->disableOriginalConstructor()->getMock();
        $this->app->expects($this->once())->method('process')->willReturn(
            $this->getMockBuilder(\Slim\Http\Response::class)->disableOriginalConstructor()->getMock()
        );
        $this->swooleRequest = $this->getMockBuilder('swoole_http_request')->disableOriginalConstructor()->getMock();
        $this->swooleResponse = $this->getMockBuilder('swoole_http_response')->disableOriginalConstructor()->getMock();
        $this->requestTransformer = $this->getMockBuilder(Bridge\RequestTransformerInterface::class)
                ->disableOriginalConstructor()->getMock();
        $this->requestTransformer->expects($this->once())->method('toSlim')->willReturn(
            $this->getMockBuilder(\Slim\Http\Request::class)->disableOriginalConstructor()->getMock()
        );

        $this->responseMerger = $this->getMockBuilder(Bridge\ResponseMergerInterface::class)
                ->disableOriginalConstructor()->getMock();

        $this->responseMerger->expects($this->once())->method('mergeToSwoole')->willReturn($this->swooleResponse);
    }

    public function testProcessReturnsSwooleResponse()
    {
        // Arrange
        $sut = new BridgeManager($this->app, $this->requestTransformer, $this->responseMerger);
        // Act
        $output = $sut->process($this->swooleRequest, $this->swooleResponse);
        // Assert
        $this->assertInstanceOf('swoole_http_response', $output);
    }
}
