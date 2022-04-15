<?php

namespace EasyAPI;

use EasyAPI\Exceptions\RouterException;
use EasyAPI\Exceptions\HttpException;

use EasyAPI\Middleware;
use EasyAPI\Request;

class Router
{
    private static $urls = [];
    private const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    private static function route(string $method, string $route, string $handler, string $class = null): void
    {

        $method = strtoupper($method);

        if(!in_array($method, self::METHODS)) {
            throw new RouterException('Invalid method ' . $method);
        }

        if(!isset(self::$urls[$method])) {
            self::$urls[$method] = [];
        }

        if(isset(self::$urls[$method][$route])) {
            throw new RouterException("Route $route already exists with method $method");
        }

        self::$urls[$method][$route] = [
            'handler' => $handler,
            'class' => $class
        ];
    }

    public static function getRouteInfo(): array
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $method = $_SERVER["REQUEST_METHOD"] ?? '';

        // clean uri
        if(preg_match('/(?<uri>.*?)[\?|#]/', $uri, $m)) {
            $uri = $m['uri'];
        }

        if(!isset(self::$urls[$method])) {
            throw new RouterException("There aren't any route with method $method");
        }

        foreach(self::$urls[$method] as $route => &$data) {

            $handler = $data['handler'];
            $class = $data['class'];
            if(preg_match('/^' . str_replace(['/'], ['\/'], $route) . '$/', $uri, $m)) {
                $path_params = [];

                foreach($m as $key => $val) {
                    if(is_numeric($key)) {
                        continue;
                    }

                    $path_params[$key] = $val;
                }

                $request = new Request();
                if($class) {
                    $middleware = new $class;
                    if(!($middleware instanceof Middleware)) {
                        throw new RouterException("Invalid middleware, must be extends of EasyAPI\\Middleware");
                    }
                    $request = $middleware->handle($request);
                }
                $path_params['request'] = $request;
                return [
                    'handler' => $handler,
                    'path_params' => $path_params,
                    // 'middleware' => $class,
                ];
            }
        }

        unset($data);
        throw new HttpException('Not found', 404);
    }

    public static function get(string $route, string $handler, string $class = null): void
    {
        self::route('GET', $route, $handler, $class);
    }

    public static function post(string $route, string $handler, string $class = null): void
    {
        self::route('POST', $route, $handler, $class);
    }

    public static function put(string $route, string $handler, string $class = null): void
    {
        self::route('PUT', $route, $handler, $class);
    }

    public static function patch(string $route, string $handler, string $class = null): void
    {
        self::route('PATCH', $route, $handler, $class);
    }

    public static function delete(string $route, string $handler, string $class = null): void
    {
        self::route('DELETE', $route, $handler, $class);
    }
}