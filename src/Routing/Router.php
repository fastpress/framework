<?php declare(strict_types=1);
/**
 * HTTP Routing object.
 *
 * PHP version 7.0
 *
 * @category   fastpress
 *
 * @author     https://github.com/samayo
 * @copyright  Copyright (c) samayo
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
 * @author     https://github.com/samayo
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
     * @param [type] $uri
     * @param [type] $callable
     */
    public function any($uri, $callable)
    {
      $this->addRoute('GET', $uri, $callable);
      $this->addRoute('POST', $uri, $callable);
      $this->addRoute('PUT', $uri, $callable);
      $this->addRoute('DELETE', $uri, $callable);
    }

    /**
     * Add a GET route.
     *
     * @param [type] $uri
     * @param [type] $callable
     */
    public function get($uri, $callable)
    {
      $this->addRoute('GET', $uri, $callable);
    }

    /**
     * Add a POST route.
     *
     * @param [type] $uri
     * @param [type] $callable
     */
    public function post($uri, $callable)
    {
      $this->addRoute('POST', $uri, $callable);
    }

    /**
     * Add a PUT route.
     *
     * @param [type] $uri
     * @param [type] $callable
     */
    public function put($uri, $callable)
    {
      $this->addRoute('PUT', $uri, $callable);
    }

    /**
     * Add a DELETE route.
     *
     * @param [type] $uri
     * @param [type] $callable
     */
    public function delete($uri, $callable)
    {
      $this->addRoute('DELETE', $uri, $callable);
    }

    /**
     * Add a route to $routes.
     *
     * @param [type] $method (GET|POST|PUT..)
     * @param [type] $uri foo.com/bar/tar
     * @param [type] $callable the callable method ex:  ('/' function () {})
     */
    protected function addRoute($method, $uri, $callable)
    { 
      // ex:     $this->routes['GET'], ['/about' => function (){}]  
      array_push($this->routes[$method], [$uri => $callable]);
    }

    /**
     * Match if URL matches a route definition.
     *
     * @param array $server
     * @param array $post | to check if there is a REST method sent via post
     */
    public function match(array $server = [], array $post)
    {
      $requestMethod = $server['REQUEST_METHOD'];
      $requestUri = $server['REQUEST_URI'];

      $restMethod = $this->getRestfullMethod($post);

      // exit if no REST method or regular method method (from $this->routes) is found
      if (null === $restMethod && !in_array($requestMethod, array_keys($this->routes))) {
          return false;
      }

      $method = $restMethod ?: $requestMethod;

      foreach ($this->routes[$method]  as $resource) {
        $args = [];
        $route = key($resource);
        $callable = reset($resource);

        if (preg_match(self::REGVAL, $route)) {
          list($args, ,$route) = $this->parseRegexRoute($requestUri, $route);
        }

        if (!preg_match("#^$route$#", $requestUri)) {
          unset($this->routes[$method]);
          continue;
        }

        if (is_string($callable) && strpos($callable, '@')) {
          list($ctrl, $method) = explode('@', $callable);
          return ['controller' => $ctrl, 'method' => $method, 'args' => $args];
        }

        if (empty($args)) {
          return $callable(null);
        } 

        return call_user_func_array($callable, $args);
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
