<?php declare(strict_types=1);
/**
 * Fastpress Application
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
namespace Fastpress;
/**
 * Application Class
 *
 * @category   fastpress
 *
 * @author     https://github.com/samayo
 */
class Application implements \ArrayAccess
{
    protected $container = array();

    public function __construct($conf)
    {
        $this->container = $conf;
    }

    public function set($data, $value) {
        $ex = explode(":", $data); 
        if(count($ex)) {
            $this->container[$ex[0]][$ex[1]] = $value;
        }
    }

    public function toJson(array $array)
    {
      return json_encode($array);
    }

    public function redirect($uri = "/")
    {
       header("Location: " . $uri);
    }

    public function offsetGet($offset)
    {
        if (array_key_exists($offset, $this->container) &&
            is_callable($this->container[$offset])) {
            return $this->container[$offset]();
        }

        return $this->container[$offset];
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->container);
    }

    public function offsetSet($offset, $value)
    {
        if (strpos($offset, ':')) {
            list($index, $subset) = explode(':', $offset, 2);
            $this->container[$index][$subset] = $value;
        }
        $this->container[$offset] = $value;
    }

    public function store(callable $callable)
    {
        return function () use ($callable) {
            static $object;
            if (null == $object) {
                $object = $callable($this->container);
            }

            return $object;
        };
    }

    public function escape($text)
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    public function server($var, $filter = null)
    {
        return $this['request']->server($var, $filter);
    }

    public function isGet()
    {
        return $this['request']->isGet();
    }

    public function isPost()
    {
        return $this['request']->isPost();
    }

    public function isPut()
    {
        return $this['request']->isPut();
    }

    public function isDelete()
    {
        return $this['request']->isDelete();
    }

    public function app($key, $value = null)
    {
        if (null === $value) {
            return $this->offsetGet($key);
        }

        $this->offsetSet($key, $value);

        return $this;
    }

    public function setResponse($header = 200, $statusText = null)
    {
        $this['response']->setResponse($header, $statusText);
    }

    public function getVar($value, $filter = null)
    {
        return $this['request']->get($value, $filter);
    }

    public function postVar($value, $filter = null)
    {
        return $this['request']->post($value, $filter);
    }

    public function any($path, $resource)
    {
        return $this['route']->any($path, $resource);
    }

    public function get($index, $modifier)
    {
        if (!is_string($modifier) && !is_callable($modifier)) {
            return $this['request']->get($index, $modifier);
        }

        return $this['route']->get($index, $modifier);
    }

    public function post($index, $modifier)
    {
        if (!is_string($modifier) && !is_callable($modifier)) {
            return $this['request']->post($index, $modifier);
        }

        return $this['route']->post($index, $modifier);
    }

    public function put($path, $resource)
    {
        return $this['route']->put($path, $resource);
    }

    public function delete($path, $resource)
    {
        return $this['route']->delete($path, $resource);
    }

    private function controllerDipatcher($resource)
    {
        $controller = $resource['controller'];
        $method = $resource['method'];
        $args = $resource['args'];

        $controller = $resource['controller'];

        if (!class_exists($controller)) {
            throw new \Exception("controller $controller does not exist");
        }

        $controller = new $controller();
        if (!method_exists($controller, $method)) {
            throw new \Exception("method $method does not exist in $controller");
        }

        (new $controller())->$method($args, $this);
    }

    public function view($block, array $variables = [])
    {
        $this['view']->view($block, $variables);
    }

    public function layout($layout, array $variables = [])
    {
        $this['view']->layout($layout, $variables);
    }

    public function run()
    {
        $input = $this['request']->requestGlobals();
        $resource = $this['route']->match($input['server'], $input['post']);

        if (is_array($resource) && !empty($resource)) {
            $dispatch = $this->controllerDipatcher($resource);
        }
    }

    public function offsetUnset($offset)
    {
    }
}
