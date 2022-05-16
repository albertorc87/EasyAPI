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
     * @param string|callable $handler class/method or callable to call for this url
     * @param string|array $middleware middleware class, can be optional
     */
    private static function route(string $method, string $route, $handler, $middleware = null): void
    {

        $invalid_type = !is_callable($handler) && !is_string($handler);
        $invalid_format = is_string($handler) && !preg_match('/@/', $handler);
        if($invalid_type || $invalid_format) {
            throw new RouterException('$handler must be a string with class and method separated by at @ or an anonymous function');
        }

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

        if(!is_string($middleware) && !is_array($middleware) && !is_null($middleware)) {
            throw new RouterException("Middleware must be string or array or null");
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

                $request = self::execMiddlewares($middleware);
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
     * Check if we have any middleware and if yes, create an instance and handle
     * @param string|array $middleware middleware class, can be optional
     *
     * @return Request object
     */
    private static function execMiddlewares($middleware): Request
    {
        $request = new Request();
        if(!$middleware) {
            return $request;
        }

        $middlewares = $middleware;
        if(is_string($middleware)) {
            $middlewares = [$middleware];
        }

        unset($middleware);

        foreach($middlewares as $middleware) {
            $middleware = new $middleware;
            if(!($middleware instanceof Middleware)) {
                throw new RouterException("Invalid middleware, must be extends of EasyAPI\\Middleware");
            }
            $request = $middleware->handle($request);
        }

        return $request;
    }

    /**
     * Save route for method GET
     * @param string $route URI
     * @param string|callable $handler class/method to call for this url
     * @param string|array $middleware middleware class, can be optional
     */
    public static function get(string $route, $handler, $middleware = null): void
    {
        self::route('GET', $route, $handler, $middleware);
    }

    /**
     * Save route for method POST
     * @param string $route URI
     * @param string|callable $handler class/method to call for this url
     * @param string|array $middleware middleware class, can be optional
     */
    public static function post(string $route, $handler, $middleware = null): void
    {
        self::route('POST', $route, $handler, $middleware);
    }

    /**
     * Save route for method PUT
     * @param string $route URI
     * @param string|callable $handler class/method to call for this url
     * @param string|array $middleware middleware class, can be optional
     */
    public static function put(string $route, $handler, $middleware = null): void
    {
        self::route('PUT', $route, $handler, $middleware);
    }

    /**
     * Save route for method PATCH
     * @param string $route URI
     * @param string|callable $handler class/method to call for this url
     * @param string|array $middleware middleware class, can be optional
     */
    public static function patch(string $route, $handler, $middleware = null): void
    {
        self::route('PATCH', $route, $handler, $middleware);
    }

    /**
     * Save route for method DELETE
     * @param string $route URI
     * @param string|callable $handler class/method to call for this url
     * @param string|array $middleware middleware class, can be optional
     */
    public static function delete(string $route, $handler, $middleware = null): void
    {
        self::route('DELETE', $route, $handler, $middleware);
    }
}