<?php

namespace EasyAPI;

use EasyAPI\Exceptions\EasyApiException;

/**
 * Class for prepare response to user and return http status code,
 * content and headers
 */
class Response
{

    private $headers = [];
    private $response = '';
    private $status_code = null;

    /**
     * Prepare the response
     * @param string $type type of response, can be json, raw or html
     * @param mixed $data response data
     * @param int $status_code http status code
     * @param array $headers extra headers in format key-value
     */
    public function __construct(
        string $type,
        $data = null,
        int $status_code = 200,
        array $headers = []
    )
    {
        $this->status_code = $status_code;

        foreach($headers as $header_name => $header_value) {
            $this->headers[strtolower($header_name)] = $header_value;
        }

        switch($type) {
            case 'raw':
                $this->raw($data);
                break;
            case 'json':
                $this->json($data);
                break;
            case 'html':
                $this->html($data);
                break;
            default:
                throw new EasyApiException('Invalid Response Type, only valids are raw, json and html');
                break;
        }
    }

    /**
     * Prepare a raw or custom response
     * @param string $data
     */
    private function raw(string $data): void
    {
        if(empty($this->headers['content-type'])) {
            $this->headers['content-type'] = 'text/plain; charset=utf-8';
        }

        $this->response = $data;
    }

    /**
     * Prepare a response with json format
     * @param $data
     */
    private function json($data): void
    {
        $this->headers['content-type'] = 'application/json; charset=utf-8';

        if($this->status_code > 399) {
            $response = [
                'status' => 'error',
                'error' => $data
            ];
        }
        else {
            $response = [
                'status' => 'success',
                'data' => $data
            ];
        }

        $this->response = json_encode($response);
    }

    /**
     * Prepare html response
     */
    private function html(string $data): void
    {
        $this->headers['content-type'] = 'text/html; charset=utf-8';

        $this->response = $data;
    }

    /**
     * Response http status code, headers and content to user
     */
    public function returnData()
    {
        foreach($this->headers as $header_name => $header_value) {
            header("$header_name: $header_value");
        }

        http_response_code($this->status_code);

        echo $this->response;
    }
}