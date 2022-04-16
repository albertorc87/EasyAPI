<?php

if(!function_exists('view')) {
    /**
     * This function create a Response object
     * @param string $type type of response, can be json, raw or html
     * @param mixed $data response data
     * @param int $status_code http status code
     * @param array $headers extra headers in format key-value
     *
     * @return EasyAPI\Response
     */
    function view(
        string $type,
        $data = null,
        int $status_code = 200,
        array $headers = []
    ): EasyAPI\Response
    {
        return new EasyAPI\Response($type, $data, $status_code, $headers);
    }
}