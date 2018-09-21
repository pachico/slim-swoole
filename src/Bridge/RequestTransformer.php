<?php

namespace Pachico\SlimSwoole\Bridge;

use Slim\Http;
use swoole_http_request;
use Dflydev\FigCookies\Cookie;
use Dflydev\FigCookies\FigRequestCookies;

class RequestTransformer implements RequestTransformerInterface
{
    const DEFAULT_SCHEMA = 'http';

    /**
     * @param swoole_http_request $request
     *
     * @return Http\Request
     *
     * @todo Handle HTTPS requests
     */
    public function toSlim(swoole_http_request $request): Http\Request
    {

        $slimRequest = Http\Request::createFromEnvironment(
            new Http\Environment([
                    'SERVER_PROTOCOL' => $request->server['server_protocol'],
                    'REQUEST_METHOD' => $request->server['request_method'],
                    'REQUEST_SCHEME' => static::DEFAULT_SCHEMA,
                    'REQUEST_URI' => $request->server['request_uri'],
                    'QUERY_STRING' => isset($request->server['query_string']) ? $request->server['query_string'] : '',
                    'SERVER_PORT' => $request->server['server_port'],
                    'REMOTE_ADDR' => $request->server['remote_addr'],
                    'REQUEST_TIME' => $request->server['request_time'],
                    'REQUEST_TIME_FLOAT' => $request->server['request_time_float']
                    ])
        );

        $slimRequest = $this->copyHeaders($request, $slimRequest);

        if ($this->isMultiPartFormData($request) || $this->isXWwwFormUrlEncoded($request)) {
            $slimRequest = $this->handlePostData($request, $slimRequest);
        }

        if ($this->isMultiPartFormData($request)) {
            $slimRequest = $this->handleUploadedFiles($request, $slimRequest);
        }

        $slimRequest = $this->copyCookies($request, $slimRequest);

        return $this->copyBody($request, $slimRequest);
    }

    /**
     * @param swoole_http_request $request
     * @param Http\Request $slimRequest
     *
     * @return Http\Request
     */
    private function copyCookies(swoole_http_request $request, Http\Request $slimRequest): Http\Request
    {
        if (empty($request->cookie)) {
            return $slimRequest;
        }

        foreach ($request->cookie as $name => $value) {
            $cookie = Cookie::create($name, $value);
            $slimRequest = FigRequestCookies::set($slimRequest, $cookie);
        }

        return $slimRequest;
    }

    /**
     * @param swoole_http_request $request
     * @param Http\Request $slimRequest
     *
     * @return Http\Request
     */
    private function copyBody(swoole_http_request $request, Http\Request $slimRequest): Http\Request
    {
        if (empty($request->rawContent())) {
            return $slimRequest;
        }

        $body = $slimRequest->getBody();
        $body->write($request->rawContent());
        $body->rewind();

        return $slimRequest->withBody($body);
    }

    /**
     * @param swoole_http_request $request
     * @param Http\Request $slimRequest
     *
     * @return Http\Request
     */
    private function copyHeaders(swoole_http_request $request, Http\Request $slimRequest): Http\Request
    {

        foreach ($request->header as $key => $val) {
            $slimRequest = $slimRequest->withHeader($key, $val);
        }

        return $slimRequest;
    }

    /**
     * @param swoole_http_request $request
     *
     * @return boolean
     */
    private function isMultiPartFormData(swoole_http_request $request): bool
    {

        if (!isset($request->header['content-type'])
            || false === stripos($request->header['content-type'], 'multipart/form-data')) {
            return false;
        }

        return true;
    }

    /**
     * @param swoole_http_request $request
     *
     * @return boolean
     */
    private function isXWwwFormUrlEncoded(swoole_http_request $request): bool
    {

        if (!isset($request->header['content-type'])
            || false === stripos($request->header['content-type'], 'application/x-www-form-urlencoded')) {
            return false;
        }

        return true;
    }


    /**
     * @param swoole_http_request $request
     * @param Http\Request $slimRequest
     *
     * @return Http\Request
     */
    private function handleUploadedFiles(swoole_http_request $request, Http\Request $slimRequest): Http\Request
    {
        if (empty($request->files) || !is_array($request->files)) {
            return $slimRequest;
        }

        $uploadedFiles = [];

        foreach ($request->files as $key => $file) {
            $uploadedFiles[$key] = new Http\UploadedFile(
                $file['tmp_name'],
                $file['name'],
                $file['type'],
                $file['size'],
                $file['error']
            );
        }

        return $slimRequest->withUploadedFiles($uploadedFiles);
    }

    /**
     * @param swoole_http_request $swooleRequest
     * @param Http\Request $slimRequest
     *
     * @return Http\Request
     */
    private function handlePostData(swoole_http_request $swooleRequest, Http\Request $slimRequest): Http\Request
    {
        if (empty($swooleRequest->post) || !is_array($swooleRequest->post)) {
            return $slimRequest;
        }

        return $slimRequest->withParsedBody($swooleRequest->post);
    }
}
