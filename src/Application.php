<?php

namespace Fastpress;

class Application implements \ArrayAccess
{
    private array $config = [];
    private array $container = [];

    public function __construct(array $config)
    {
        $this->config = $config;
        if (!empty($this->config['services']) && is_array($this->config['services'])) {
            foreach ($this->config['services'] as $name => $resolver) {
                $this->register($name, $resolver);
            }
        }
    }

    /**
     * Registers a service in the application.
     *
     * @param string $name Name of the service.
     * @param callable $resolver Service resolver function.
     */
    public function register(string $name, callable $resolver): void
    {
        $this->container['services'][$name] = function () use ($resolver) {
            static $service;
            if (null === $service) {
                $service = $resolver();
            }
            return $service;
        };
    }

    /**
     * Defines a route that responds to GET HTTP method.
     *
     * @param string $path URI path for the route.
     * @param mixed $resource Handler for the route.
     */
    public function get(string $path, $resource): void
    {
        $this->container['services']['router']()->get($path, $resource);
    }

    public function post(string $path, $resource): void
    {
        $this->container['services']['router']()->post($path, $resource);
    }

    public function any(string $path, $resource): void
    {
        $this->container['services']['router']()->any($path, $resource);
    }

    public function delete(string $path, $resource): void
    {
        $this->container['services']['router']()->delete($path, $resource);
    }

    public function set(string $key, $value): void
    {
        if (strpos($key, ':') !== false) {
            list($container, $key) = explode(':', $key);
            $this->config[$container][$key] = $value;
            return;
        }

        $this->container[$key] = $value;
    }

    public function offsetExists($offset): bool
    {
        $parts = explode(':', $offset);
        if (count($parts) === 2) {
            return isset($this->container[$parts[0]][$parts[1]]);
        }

        return isset($this->container[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        $parts = explode(':', $offset);
        if (count($parts) === 2) {
            return $this->container[$parts[0]][$parts[1]] ?? null;
        }

        if (isset($this->container['services'][$offset])) {
            return $this->container['services'][$offset]();
        }

        if ($this->config[$offset]) {
            return $this->config[$offset];
        }

        return $this->container[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        $parts = explode(':', $offset);
        if (count($parts) === 2) {
            $this->container[$parts[0]][$parts[1]] = $value;
            return;
        }

        $this->container[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        $parts = explode(':', $offset);
        if (count($parts) === 2) {
            unset($this->container[$parts[0]][$parts[1]]);
            return;
        }

        unset($this->container[$offset]);
    }

    public function run(): void
    {
        $input = $this->container['services']["request"]()->requestGlobals();
        $result = $this->container['services']["router"]()->match(
            $input["server"],
            $input["post"]
        );

        if ($result === null) {
            header("HTTP/1.0 404 Not Found");
            die('Invalid route');
        }

        list($args, $resource) = $result;
        list($controller, $method) = explode('@', $resource);

        if (!$controller || !$method) {
            // header("HTTP/1.0 404 Not Found");
        }

        $this->Container($controller, $method, $args);
    }

    public function Container($controller, $method, $args): void
    {
        $controllerInstance = $this->recursiveClassLoader($controller);
        $reflection = new \ReflectionMethod($controllerInstance, $method);

        $params = $reflection->getParameters();
        $args = [];

        foreach ($params as $param) {
            $paramType = $param->getType();
            $paramName = $param->getName();

            if ($paramName === 'view') {
                $args[] = new \Fastpress\View\View($this);
                continue;
            } else {
                $args[] = $this->container['services'][$paramName]();
            }
        }

        call_user_func_array([$controllerInstance, $method], $args);
    }

    public function recursiveClassLoader($className)
    {
        if (strpos($className, 'Controller') !== false) {
            $className = $this->config['namespaces']['controller'] . $className;
        }

        $parts = explode('\\', $className);
        $shortClassName = strtolower(end($parts));

        if (isset($this->config['services'][$shortClassName])) {
            return $this->config['services'][$shortClassName]($this);
        }
        $reflection = new \ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return $reflection->newInstance();
        }

        $params = $constructor->getParameters();
        $dependencies = [];

        foreach ($params as $param) {
            $paramType = $param->getType();
            if ($paramType->getName() === 'PDO') {
                $dependencies[] = new \PDO(
                    $this->createPdoDsn($this->config['database']['mysql']),
                    $this->config['database']['mysql']['username'],
                    $this->config['database']['mysql']['password'],
                    [
                        \PDO::ATTR_EMULATE_PREPARES => false,
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    ]
                );
                continue;
            }

            if (!$paramType->isBuiltin()) {
                $dependencies[] = $this->recursiveClassLoader($paramType->getName());
            }
        }

        return $reflection->newInstanceArgs($dependencies);
    }

    public function createPdoDsn($dbConfig)
    {
        if ($dbConfig['driver'] == 'mysql') {
            return "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
        }

        return null;
    }
}
