<?php

namespace Pachico\SlimSwooleUnitTest\Bridge;

use Pachico\SlimSwoole\Bridge;
use Slim\Http;
use Dflydev\FigCookies\Cookies;
use Dflydev\FigCookies\FigRequestCookies;

class RequestTransformerTest extends \Pachico\SlimSwooleUnitTest\AbstractTestCase
{

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $swooleRequest;

    /**
     * @var Bridge\RequestTransformer
     */
    private $sut;

    public function setUp()
    {
        parent::setUp();

        $this->swooleRequest = $this->getMockBuilder('\swoole_http_request')->disableOriginalConstructor()->getMock();
        $this->swooleRequest->server = [
            'server_protocol' => 'HTTP/1.1',
            'request_method' => 'POST',
            'request_uri' => '/my/uri',
            'query_string' => 'foo=bar',
            'server_port' => '6789',
            'remote_addr' => '123.123.123.123',
            'request_time' => '1514764800',
            'request_time_float' => '1514764800.000',
        ];
        $this->swooleRequest->header = [
            'host' => 'example.com',
        ];

        $this->sut = new Bridge\RequestTransformer();
    }

    public function testToSlimReturnsSlimRequest()
    {
        // Arrange
        // Act
        $output = $this->sut->toSlim($this->swooleRequest);
        // Assert
        $this->assertInstanceOf(Http\Request::class, $output);
    }

    public function testBodyGetsCopiedCorrectly()
    {
        // Arrange
        $bodyContent = 'This is my body.';
        $this->swooleRequest->expects($this->any())->method('rawContent')->willReturn($bodyContent);
        // Act
        $output = $this->sut->toSlim($this->swooleRequest);
        // Assert
        $this->assertSame($bodyContent, $output->getBody()->getContents());
    }

    public function testPostDataGetsCopiedIfExistsAndIsMultipartFormData()
    {
        // Arrange
        $this->swooleRequest->header = array_merge(
            $this->swooleRequest->header,
            [
                'content-type' => 'multipart/form-data',
                'foo' => 'bar'
            ]
        );
        $this->swooleRequest->post = [
            'foo' => 'bar'
        ];
        // Act
        $output = $this->sut->toSlim($this->swooleRequest);
        // Assert
        $this->assertSame([
            'foo' => 'bar'
            ], $output->getParsedBody());
    }

    public function testPostDataGetsCopiedIfExistsAndXWwwFormUrlEncoded()
    {
        // Arrange
        $this->swooleRequest->header = array_merge(
            $this->swooleRequest->header,
            [
                'content-type' => 'application/x-www-form-urlencoded'
            ]
        );
        $this->swooleRequest->post = [
            'foo' => 'bar'
        ];
        // Act
        $output = $this->sut->toSlim($this->swooleRequest);
        // Assert
        $this->assertSame([
            'foo' => 'bar'
            ], $output->getParsedBody());
    }

    public function testUploadedFilesAreCopiedProperty()
    {
        // Arrange
        $this->swooleRequest->header = array_merge(
            $this->swooleRequest->header,
            ['content-type' => 'multipart/form-data']
        );
        $this->swooleRequest->files = [
            'name1' => [
                'tmp_name' => 'tmp1',
                'name' => 'name1',
                'type' => 'type1',
                'size' => 77,
                'error' => 0
            ],
            'name2' => [
                'tmp_name' => 'tmp2',
                'name' => 'name2',
                'type' => 'type2',
                'size' => 88,
                'error' => 0
            ],
        ];

        // Act
        $output = $this->sut->toSlim($this->swooleRequest);
        // Assert
        $this->assertNotEmpty($output->getUploadedFiles());

        foreach ($output->getUploadedFiles() as $uploadedFile) {
            $this->assertInstanceOf(Http\UploadedFile::class, $uploadedFile);
        }

        $this->assertSame($output->getUploadedFiles()['name1']->getClientFilename(), 'name1');
        $this->assertSame($output->getUploadedFiles()['name1']->getClientMediaType(), 'type1');
        $this->assertSame($output->getUploadedFiles()['name1']->getError(), 0);
        $this->assertSame($output->getUploadedFiles()['name1']->getSize(), 77);
        $this->assertSame($output->getUploadedFiles()['name2']->getClientFilename(), 'name2');
        $this->assertSame($output->getUploadedFiles()['name2']->getClientMediaType(), 'type2');
        $this->assertSame($output->getUploadedFiles()['name2']->getError(), 0);
        $this->assertSame($output->getUploadedFiles()['name2']->getSize(), 88);
    }

    public function testCookiesAreCopiedProperly()
    {
        $this->swooleRequest->cookie = [
            'some-cookie-1' => 'some-value-1',
            'some-cookie-2' => 'some-value-2',
            'some-cookie-3' => 'some-value-3',
        ];

        // Act
        $output = $this->sut->toSlim($this->swooleRequest);

        // Assert
        $cookies = Cookies::fromRequest($output)->getAll();
        $this->assertEquals(count($cookies), 3);
        $this->assertEquals(FigRequestCookies::get($output, 'some-cookie-2')->getValue(), 'some-value-2');
    }

    public function testHostHeaderIsCopiedProperly()
    {
        // Act
        $output = $this->sut->toSlim($this->swooleRequest);
        $this->assertEquals($output->getUri()->getHost(), 'example.com');
    }
}
