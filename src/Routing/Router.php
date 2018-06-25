<?php declare(strict_types=1);
/**
 * HTTP Routing object.
 *
 * PHP version 7.0
 *
 * @category   fastpress
 *
 * @author     Simon Daniel <samayo@protonmail.ch>
 * @copyright  Copyright (c) 2017 Simon Daniel
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @version    0.1.0
 */

namespace Fastpress\Routing;

/**
 * HTTP Routing object.
 *
 * @category   fastpress
 *
 * @author     Simon Daniel <samayo@protonmail.ch>
 */
class Router
{
    public $routes = [
      'GET' => [],
      'POST' => [],
      'PUT' => [],
      'DELETE' => [],
    ];

    public $patterns = [
      ':any' => '.*',
      ':id' => '[0-9]+',
      ':slug' => '[a-z-0-9\-]+',
      ':name' => '[a-zA-Z]+',
    ];

    const REGVAL = '/({:.+?})/';

    /**
     * Add new routes to $routes array.
     *
     * @param [type] $path
     * @param [type] $handler
     */
    public function any($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
        $this->addRoute('POST', $path, $handler);
        $this->addRoute('PUT', $path, $handler);
        $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Add a GET route.
     *
     * @param [type] $path
     * @param [type] $handler
     */
    public function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Add a POST route.
     *
     * @param [type] $path
     * @param [type] $handler
     */
    public function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Add a PUT route.
     *
     * @param [type] $path
     * @param [type] $handler
     */
    public function put($path, $handler)
    {
        $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Add a DELETE route.
     *
     * @param [type] $path
     * @param [type] $handler
     */
    public function delete($path, $handler)
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Add a route to $routes.
     *
     * @param [type] $method
     * @param [type] $path
     * @param [type] $handler
     */
    protected function addRoute($method, $path, $handler)
    {
        array_push($this->routes[$method], [$path => $handler]);
    }

    /**
     * Match if URL matches a route definition.
     *
     * @param array $server
     * @param array $post
     */
    public function match(array $server = [], array $post)
    {
        $requestMethod = $server['REQUEST_METHOD'];
        $requestUri = $server['REQUEST_URI'];

        $restMethod = $this->getRestfullMethod($post);

        // check if REST request is made
        if (null === $restMethod && !in_array($requestMethod, array_keys($this->routes))) {
            return false;
        }

        $method = $restMethod ?: $requestMethod;

        foreach ($this->routes[$method]  as $resource) {
            $args = [];
            $route = key($resource);
            $handler = reset($resource);

            if (preg_match(self::REGVAL, $route)) {
                list($args, , $route) = $this->parseRegexRoute($requestUri, $route);
            }

            if (!preg_match("#^$route$#", $requestUri)) {
                unset($this->routes[$method]);
                continue;
            }

            if (is_string($handler) && strpos($handler, '@')) {
                list($ctrl, $method) = explode('@', $handler);

                return ['controller' => $ctrl, 'method' => $method, 'args' => $args];
            }

            if (empty($args)) {
                return $handler();
            }

            return call_user_func_array($handler, $args);
        }
    }

    /**
     * Check and return a REST request (if defined).
     *
     * @param [type] $postVar
     */
    protected function getRestfullMethod($postVar)
    {
        if (array_key_exists('_method', $postVar)) {
            $method = strtoupper($postVar['_method']);
            if (in_array($method, array_keys($this->routes))) {
                return $method;
            }
        }
    }

    /**
     * Regex parser for named routes.
     *
     * @param [type] $requestUri
     * @param [type] $resource
     */
    protected function parseRegexRoute($requestUri, $resource)
    {
        $route = preg_replace_callback(self::REGVAL, function ($matches) {
            $patterns = $this->patterns;
            $matches[0] = str_replace(['{', '}'], '', $matches[0]);
            if (in_array($matches[0], array_keys($patterns))) {
                return  $patterns[$matches[0]];
            }
        }, $resource);

        $regUri = explode('/', $resource);
        $args = array_diff(
              array_replace(
                  $regUri,
              explode('/', $requestUri)
            ),
            $regUri
          );

        return [array_values($args), $resource, $route];
    }
}
