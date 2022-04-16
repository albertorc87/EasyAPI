<?php

namespace EasyAPI;

use EasyAPI\Router;
use EasyAPI\Response;
use EasyAPI\Exceptions\HttpException;
use EasyAPI\Exceptions\EasyApiException;
use Throwable;

/**
 * Main class
 */
class App {

    public function __construct()
    {
        $this->checkEnv();
    }

    /**
     * Check if the environment is defined correctly
     */
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

    /**
     * This method is responsible for receiving the URI,
     * checking that we have added it to the router and if so, calling the related
     * class and method to that URI
     */
    public function send()
    {

        try {
            $route_info = Router::getRouteInfo();

            $path_params = $route_info['path_params'];
            $handler = $route_info['handler'];

            if(is_callable($handler) || !preg_match('/@/', $handler)) {
                $response = call_user_func_array($handler, $path_params);
            }
            else {
                list($class, $method) = explode('@', $handler, 2);
                $response = call_user_func_array([new $class, $method], $path_params);
            }

            if(!($response instanceof Response)) {
                throw new EasyApiException('Invalid response format');
            }

            $response->returnData();
        }
        catch(HttpException $e) {
            $response = new Response('json', $e->getMessage(), $e->getCode());
            $response->returnData();
        }
        catch(Throwable $e) {
            if($_ENV['DEBUG_MODE']) {
                throw $e;
            }
            $response = new Response('json', 'Internal Server Error', 500);
            $response->returnData();
        }
    }
}