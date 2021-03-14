<?php

namespace Pachico\SlimSwooleUnitTest\Bridge;

use Pachico\SlimSwoole\Bridge;
use Psr\Http\Message\ResponseInterface;
use Slim\Http;

class ResponseMergerTest extends \Pachico\SlimSwooleUnitTest\AbstractTestCase
{

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $psrResponse;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $swooleResponse;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $body;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $app;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $container;

    /**
     * @var Bridge\ResponseMerger
     */
    private $sut;

    public function setUp(): void
    {
        parent::setUp();

        $this->swooleResponse = $this->getMockBuilder('\swoole_http_response')->disableOriginalConstructor()->getMock();
        $this->body = $this->getMockForAbstractClass(\Psr\Http\Message\StreamInterface::class);
        $this->psrResponse = $this->getMockBuilder(ResponseInterface::class)->getMockForAbstractClass();
        $this->psrResponse->expects($this->any())->method('getBody')->willReturn($this->body);
        $this->container = $this->getMockBuilder(\Slim\Container::class)->disableOriginalConstructor()->getMock();
        $this->app = $this->getMockBuilder(\Slim\App::class)->disableOriginalConstructor()->getMock();
        $this->app->expects($this->any())->method('getContainer')->willReturn($this->container);

        $this->sut = new Bridge\ResponseMerger($this->app);
    }

    public function testMergeToSwooleReturnsPsrResponse()
    {
        // Arrange
        // Act
        $output = $this->sut->mergeToSwoole($this->psrResponse, $this->swooleResponse);
        // Assert
        $this->assertInstanceOf('\swoole_http_response', $output);
    }

    public function testContentLengthGetsCopiedIfSettingsSaySo()
    {
        // Arrange
        $this->container->expects($this->once())->method('get')->with('settings')->willReturn([
            'addContentLengthHeader' => true
        ]);
        $this->body->expects($this->any())->method('getSize')->willReturn(77);
        $this->swooleResponse->expects($headerSpy = $this->once())->method('header')->with('Content-Length', '77');
        // Act
        $this->sut->mergeToSwoole($this->psrResponse, $this->swooleResponse);
        // Assert
        $this->assertSame(1, $headerSpy->getInvocationCount());
    }

    public function testContentLengthDoesNotGetCopiedIfSettingsSaySo()
    {
        // Arrange
        $this->container->expects($this->once())->method('get')->with('settings')->willReturn([
            'addContentLengthHeader' => false
        ]);
        $this->body->expects($this->any())->method('getSize')->willReturn(77);
        $this->swooleResponse->expects($headerSpy = $this->never())->method('header')->with('Content-Length', '77');
        // Act
        $this->sut->mergeToSwoole($this->psrResponse, $this->swooleResponse);
        // Assert
        $this->assertSame(0, $headerSpy->getInvocationCount());
    }

    public function testHeadersGetCopied()
    {
        // Arrange
        $this->psrResponse->expects($this->any())->method('getHeaders')->willReturn([
            'foo' => ['bar'],
            'fiz' => ['bam']
        ]);
        $this->psrResponse->method('withoutHeader')->willReturn($this->psrResponse);

        $this->swooleResponse->expects($headerSpy = $this->exactly(2))->method('header');
        // Act
        $this->sut->mergeToSwoole($this->psrResponse, $this->swooleResponse);
        // Assert
        $this->assertSame(2, $headerSpy->getInvocationCount());
    }

    public function testCookiesShouldBeMergedWithCookieMethod()
    {
        $psrResponseWithoutCookies = clone $this->psrResponse;
        $psrResponseWithoutCookies->method('getHeaders')->willReturn([]);
        $this->psrResponse->method('withoutHeader')->willReturn($psrResponseWithoutCookies);
        $expires = new \Datetime('+2 hours');
        
        $cookieArray = [
            'Cookie1=Value1; Domain=some-domain; Path=/; Expires=' .
                $expires->format(\DateTime::COOKIE) . ' GMT; Secure; HttpOnly',
        ];

        // Arrange
        $this->psrResponse->expects($this->any())->method('getHeaders')->willReturn([
            'Set-Cookie' => $cookieArray
        ]);
        $this->psrResponse->method('getHeader')->willReturn($cookieArray);
        $this->psrResponse->method('hasHeader')->willReturn(true);
        $this->swooleResponse->expects($headerSpy = $this->exactly(0))->method('header');
        $this->swooleResponse->expects($cookieSpy = $this->exactly(1))->method('cookie')
            ->with('Cookie1', 'Value1', $expires->getTimestamp(), '/', 'some-domain', true, true);
        // Act
        $this->sut->mergeToSwoole($this->psrResponse, $this->swooleResponse);
        // Assert
        $this->assertSame(0, $headerSpy->getInvocationCount());
        $this->assertSame(1, $cookieSpy->getInvocationCount());
    }
    
    public function testBodyContentGetsCopiedIfNotEmpty()
    {
        // Arrange
        $this->body->expects($this->once())->method('getSize')->willReturn(3);
        $this->body->expects($this->once())->method('isSeekable')->willReturn(true);
        $this->body->expects($rewindSpy = $this->once())->method('rewind')->willReturn(null);
        $this->body->expects($this->once())->method('rewind')->willReturn(null);
        $this->body->expects($this->once())->method('getContents')->willReturn('abc');
        $this->swooleResponse->expects($writeSpy = $this->once())->method('write')->with('abc');
        // Act
        $this->sut->mergeToSwoole($this->psrResponse, $this->swooleResponse);

        // Assert
        $this->assertSame(1, $rewindSpy->getInvocationCount());
        $this->assertSame(1, $writeSpy->getInvocationCount());
    }

    public function testStatusCodeGetsCopied()
    {
        // Arrange
        $this->psrResponse->expects($this->once())->method('getStatusCode')->willReturn(235);
        $this->psrResponse->expects($this->once())->method('getReasonPhrase')->willReturn('Unknown code');
        $this->swooleResponse->expects($setStatusSpy = $this->once())->method('status')->with(235, 'Unknown code');
        // Act
        $this->sut->mergeToSwoole($this->psrResponse, $this->swooleResponse);

        // Assert
        $this->assertSame(1, $setStatusSpy->getInvocationCount());
    }
}
