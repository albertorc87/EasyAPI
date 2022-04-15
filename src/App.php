<?php

namespace EasyAPI;

use EasyAPI\Router;
use EasyAPI\Response;
use EasyAPI\Exceptions\HttpException;
use EasyAPI\Exceptions\EasyApiException;
use Exception;

class App {

    public function __construct()
    {
        $this->checkEnv();
    }

    private function checkEnv(): void
    {
        if(!isset($_ENV['DEBUG_MODE'])) {
            throw new EasyApiException('You must define DEBUG_MODE env with value "true" or "false"');
        }

        if(is_bool($_ENV['DEBUG_MODE'])) {
            return;
        }

        if(!is_string($_ENV['DEBUG_MODE'])) {
            throw new EasyApiException('Invalid format in DEBUG_MODE env, valid values "true" or "false"');
        }

        $debug_mode = strtolower($_ENV['DEBUG_MODE']);
        if($debug_mode === 'true') {
            $_ENV['DEBUG_MODE'] = true;
        }
        elseif($debug_mode === 'false') {
            $_ENV['DEBUG_MODE'] = false;
        }
        else {
            throw new EasyApiException('Invalid value in DEBUG_MODE env, only valid values "true" or "false"');
        }
    }

    public function send()
    {

        try {
            $route_info = Router::getRouteInfo();

            $path_params = $route_info['path_params'];
            $handler = $route_info['handler'];

            list($class, $method) = explode('/', $handler, 2);

            $response = call_user_func_array([new $class, $method], $path_params);

            if(!($response instanceof Response)) {
                throw new Exception('Invalid format');
            }

            $response->returnData();
        }
        catch(HttpException $e) {
            $response = new Response('json', $e->getMessage(), $e->getCode());
            $response->returnData();
        }
        catch(Exception $e) {
            if($_ENV['DEBUG_MODE']) {
                throw $e;
            }
            $response = new Response('json', 'Internal Server Error', 500);
            $response->returnData();
        }
    }
}