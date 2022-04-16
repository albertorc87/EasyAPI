<?php

namespace EasyAPI;

/**
 * Set data from middleware to later access it from a controller
 */
class Request
{
    private $data = [];

    public function setData(string $key, $value)
    {
        $this->data[$key] = $value;
    }

    public function getData(string $key)
    {
        return $this->data[$key] ?? null;
    }
}
