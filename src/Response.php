<?php

namespace EasyAPI;

class Response
{

    private $headers = [];
    private $response = '';
    private $status_code = null;

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
                //TODO: Exception
                break;
        }
    }

    private function raw(string $data): void
    {
        if(empty($this->headers['content-type'])) {
            $this->headers['content-type'] = 'text/plain';
        }

        $this->response = $data;
    }

    private function json($data): void
    {
        $this->headers['content-type'] = 'application/json';

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

    private function html(string $data): void
    {
        $this->headers['content-type'] = 'text/html';

        $this->response = $data;
    }

    public function returnData()
    {
        foreach($this->headers as $header_name => $header_value) {
            header("$header_name: $header_value");
        }

        http_response_code($this->status_code);

        echo $this->response;
    }
}