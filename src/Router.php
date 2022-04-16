<?php

namespace EasyAPI;

use EasyAPI\Exceptions\RouterException;
use EasyAPI\Exceptions\HttpException;

use EasyAPI\Middleware;
use EasyAPI\Request;

/**
 * This class add routes to system
 */
class Router
{
    private static $urls = [];
    private const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Save routes in $urls var
     * @param string $method http method
     * @param string $route URI
     * @param string $handler class/method to call for this url
     * @param string $middleware middleware class, can be optional
     */
    private static function route(string $method, string $route, string $handler, string $middleware = null): void
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
            'middleware' => $middleware
        ];
    }

    /**
     * Get class, method and middleware associate an URI
     *
     * @return array with class, method and params
    */
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
            $middleware = $data['middleware'];
            if(preg_match('/^' . str_replace(['/'], ['\/'], $route) . '$/', $uri, $m)) {
                $path_params = [];

                foreach($m as $key => $val) {
                    if(is_numeric($key)) {
                        continue;
                    }

                    $path_params[$key] = $val;
                }

                $request = new Request();
                if($middleware) {
                    $middleware = new $middleware;
                    if(!($middleware instanceof Middleware)) {
                        throw new RouterException("Invalid middleware, must be extends of EasyAPI\\Middleware");
                    }
                    $request = $middleware->handle($request);
                }
                $path_params['request'] = $request;
                return [
                    'handler' => $handler,
                    'path_params' => $path_params,
                ];
            }
        }

        unset($data);
        throw new HttpException('Not found', 404);
    }

    /**
     * Save route for method GET
     * @param string $route URI
     * @param string $handler class/method to call for this url
     * @param string $middleware middleware class, can be optional
     */
    public static function get(string $route, string $handler, string $middleware = null): void
    {
        self::route('GET', $route, $handler, $middleware);
    }

    /**
     * Save route for method POST
     * @param string $route URI
     * @param string $handler class/method to call for this url
     * @param string $middleware middleware class, can be optional
     */
    public static function post(string $route, string $handler, string $middleware = null): void
    {
        self::route('POST', $route, $handler, $middleware);
    }

    /**
     * Save route for method PUT
     * @param string $route URI
     * @param string $handler class/method to call for this url
     * @param string $middleware middleware class, can be optional
     */
    public static function put(string $route, string $handler, string $middleware = null): void
    {
        self::route('PUT', $route, $handler, $middleware);
    }

    /**
     * Save route for method PATCH
     * @param string $route URI
     * @param string $handler class/method to call for this url
     * @param string $middleware middleware class, can be optional
     */
    public static function patch(string $route, string $handler, string $middleware = null): void
    {
        self::route('PATCH', $route, $handler, $middleware);
    }

    /**
     * Save route for method DELETE
     * @param string $route URI
     * @param string $handler class/method to call for this url
     * @param string $middleware middleware class, can be optional
     */
    public static function delete(string $route, string $handler, string $middleware = null): void
    {
        self::route('DELETE', $route, $handler, $middleware);
    }
}