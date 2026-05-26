<?php

declare(strict_types=1);

namespace Fastpress;

use Fastpress\Memory\Database;
use RuntimeException;
use Closure;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

/**
 * The Application class is the central point of the Fastpress framework.
 * It handles service registration, dependency injection, routing, and request processing.
 */
class Application implements \ArrayAccess
{
    /** @var array The application configuration. */
    private array $config;

    /** @var array Registered services and their resolvers. */
    private array $services = [];

    /** @var array Resolved service instances. */
    private array $instances = [];

    /** @var Database|null The database connection instance. */
    private ?Database $database = null;

    /**
     * Constructor for the Application class.
     *
     * @param array $config The application configuration.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->registerDefaultServices();
    }

    /**
     * Registers default services like 'config', 'pdo' (if database config exists),
     * and any user-defined services from the configuration.
     */
    private function registerDefaultServices(): void
    {
        // Register the config service
        $this->register('config', fn() => $this->config);

        // Register the PDO service if database configuration exists
        if (isset($this->config['database']['mysql'])) {
            $this->register('pdo', function () {
                if ($this->database === null) {
                    $this->database = new Database($this->config['database']['mysql']);
                }
                return $this->database->getConnection();
            });
        }

        // Register user-defined services
        if (isset($this->config['services'])) {
            foreach ($this->config['services'] as $name => $resolver) {
                $this->register($name, $resolver);
            }
        }
    }

    /**
     * Registers a service with the application.
     *
     * @param string  $name     The name of the service.
     * @param Closure $resolver A closure that resolves the service instance.
     * @return self
     */
    public function register(string $name, Closure $resolver): self
    {
        $this->services[$name] = $resolver;
        unset($this->instances[$name]);
        return $this;
    }

    /**
     * Resolves a service instance by name.
     *
     * @param string $name The name of the service.
     * @return mixed The resolved service instance.
     * @throws RuntimeException If the service is not found.
     */
    public function resolve(string $name): mixed
    {
        if (!isset($this->services[$name])) {
            throw new RuntimeException("Service '{$name}' not found");
        }

        if (!isset($this->instances[$name])) {
            $this->instances[$name] = ($this->services[$name])($this);
        }

        return $this->instances[$name];
    }

    /**
     * Checks if a service is registered.
     *
     * @param string $name The name of the service.
     * @return bool True if the service is registered, false otherwise.
     */
    public function has(string $name): bool
    {
        return isset($this->services[$name]);
    }

    /**
     * Registers a GET route with the router.
     *
     * @param string $path    The path for the route.
     * @param string $handler The route handler (Controller@method).
     * @return self
     */
    public function get(string $path, string $handler): self
    {
        $this->resolve('router')->get($path, $handler);
        return $this;
    }

    /**
     * Registers a POST route with the router.
     *
     * @param string $path    The path for the route.
     * @param string $handler The route handler (Controller@method).
     * @return self
     */
    public function post(string $path, string $handler): self
    {
        $this->resolve('router')->post($path, $handler);
        return $this;
    }

    /**
     * Registers a PUT route with the router.
     *
     * @param string $path    The path for the route.
     * @param string $handler The route handler (Controller@method).
     * @return self
     */
    public function put(string $path, string $handler): self
    {
        $this->resolve('router')->put($path, $handler);
        return $this;
    }

    /**
     * Registers a DELETE route with the router.
     *
     * @param string $path    The path for the route.
     * @param string $handler The route handler (Controller@method).
     * @return self
     */
    public function delete(string $path, string $handler): self
    {
        $this->resolve('router')->delete($path, $handler);
        return $this;
    }

    /**
     * Registers a PATCH route with the router.
     *
     * @param string $path    The path for the route.
     * @param string $handler The route handler (Controller@method).
     * @return self
     */
    public function patch(string $path, string $handler): self
    {
        $this->resolve('router')->patch($path, $handler);
        return $this;
    }

    /**
     * Registers a route that matches any HTTP method with the router.
     *
     * @param string $path    The path for the route.
     * @param string $handler The route handler (Controller@method).
     * @return self
     */
    public function any(string $path, string $handler): self
    {
        $this->resolve('router')->any($path, $handler);
        return $this;
    }

    /**
     * Runs the application. This method handles the request processing flow.
     */
    public function run(): void
    {
        try {
            /** @var \Fastpress\Routing\Router $router */
            $router = $this->resolve('router');
            /** @var \Fastpress\Http\Request $request */
            $request = $this->resolve('request');

            // Match the request to a route
            $route = $router->match($_SERVER, $_POST);

            if ($route === null) {
                throw new RuntimeException('Route not found', 404);
            }

            // Set URL parameters in the request object
            $request->setUrlParams($route['params'] ?? []);

            // Parse the route handler to get the controller and method
            [$controller, $method] = $this->parseHandler($route['handler']);

            // Resolve the controller instance
            $instance = $this->resolveController($controller);

            // Build the controller action as a callable
            $action = function () use ($instance, $method, $route) {
                $this->executeAction($instance, $method, $route['params'] ?? []);
            };

            // Run middleware pipeline, then execute action
            $this->runMiddleware($route['middleware'] ?? [], $action);

        } catch (RuntimeException $e) {
            $this->handleError($e);
        }
    }

    /**
     * Runs the middleware pipeline.
     *
     * Middleware can be a class name with a handle(Request, Response, Closure $next) method,
     * or a callable with the same signature.
     *
     * @param array    $middleware The middleware stack.
     * @param callable $action    The final action to execute after all middleware.
     */
    private function runMiddleware(array $middleware, callable $action): void
    {
        if (empty($middleware)) {
            $action();
            return;
        }

        $request = $this->resolve('request');
        $response = $this->resolve('response');

        $current = array_shift($middleware);
        $next = function () use ($middleware, $action) {
            $this->runMiddleware($middleware, $action);
        };

        if (is_callable($current)) {
            $current($request, $response, $next);
        } elseif (is_string($current) && class_exists($current)) {
            $instance = $this->resolveClass($current);
            $instance->handle($request, $response, $next);
        } else {
            // Unknown middleware, skip and continue
            $next();
        }
    }

    /**
     * Resolves a class by autowiring its constructor dependencies.
     *
     * @param string $className The fully qualified class name.
     * @return object The resolved instance.
     */
    private function resolveClass(string $className): object
    {
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return $reflection->newInstance();
        }

        $dependencies = $this->resolveDependencies($constructor->getParameters());
        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Parses the route handler string to extract the controller and method.
     *
     * @param string $handler The route handler string (Controller@method).
     * @return array An array containing the controller class and method name.
     * @throws RuntimeException If the handler format is invalid.
     */
    private function parseHandler(string $handler): array
    {
        if (!str_contains($handler, '@')) {
            throw new RuntimeException("Invalid handler format: {$handler}");
        }

        [$controller, $method] = explode('@', $handler, 2);

        if (empty($controller) || empty($method)) {
            throw new RuntimeException("Invalid handler format: {$handler}");
        }

        return [$controller, $method];
    }

    /**
     * Resolves the controller instance.
     *
     * @param string $controller The controller class name.
     * @return object The controller instance.
     * @throws RuntimeException If the controller cannot be resolved.
     */
    private function resolveController(string $controller): object
    {
        if (str_contains($controller, 'Controller')) {
            $namespace = $this->config['namespaces']['controller'] ?? '';
            $controller = $namespace . $controller;
        }

        try {
            $reflection = new ReflectionClass($controller);

            if (!$reflection->isInstantiable()) {
                throw new RuntimeException("Controller {$controller} is not instantiable");
            }

            $constructor = $reflection->getConstructor();

            if (!$constructor) {
                return $reflection->newInstance();
            }

            // Resolve constructor dependencies
            $dependencies = $this->resolveDependencies($constructor->getParameters());
            return $reflection->newInstanceArgs($dependencies);

        } catch (\ReflectionException $e) {
            throw new RuntimeException("Failed to resolve controller: {$controller}", 0, $e);
        }
    }

    /**
     * Executes the controller action.
     *
     * @param object $controller The controller instance.
     * @param string $method     The method name.
     * @param array  $params     Route parameters.
     * @throws RuntimeException If the action cannot be executed.
     */
    private function executeAction(object $controller, string $method, array $params = []): void
    {
        try {
            $reflection = new ReflectionMethod($controller, $method);
            $dependencies = $this->resolveDependencies($reflection->getParameters());

            // Share global view data with the view service
            if ($this->has('view')) {
                $view = $this->resolve('view');
                $view->share('app', [
                    'config' => $this->config,
                    'params' => $params,
                    'site' => $this->config['site'] ?? [],
                ]);
            }

            $reflection->invokeArgs($controller, $dependencies);

        } catch (\ReflectionException $e) {
            throw new RuntimeException("Failed to execute controller action", 0, $e);
        }
    }

    /**
     * Resolves dependencies for a method or constructor.
     *
     * @param array $parameters An array of ReflectionParameter objects.
     * @return array An array of resolved dependencies.
     */
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $this->resolveDependency($parameter);
            if ($dependency !== null) {
                $dependencies[] = $dependency;
            }
        }

        return $dependencies;
    }

    /**
     * Returns the complete application configuration.
     *
     * @return array The application configuration.
     */
    public function config(): array
    {
        return $this->config;
    }

    /**
     * Resolves a single dependency.
     *
     * @param ReflectionParameter $parameter The parameter to resolve.
     * @return mixed The resolved dependency.
     * @throws RuntimeException If the dependency cannot be resolved.
     */
    private function resolveDependency(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if (!$type || $type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            throw new RuntimeException("Cannot resolve parameter: {$parameter->getName()}");
        }

        $className = $type->getName();
        $serviceName = lcfirst(basename(str_replace('\\', '/', $className)));

        if ($this->has($serviceName)) {
            return $this->resolve($serviceName);
        }

        // Prevent circular dependencies
        static $resolvingStack = [];
        if (in_array($className, $resolvingStack)) {
            throw new RuntimeException("Circular dependency detected for {$className}");
        }
        $resolvingStack[] = $className;

        try {
            $reflection = new ReflectionClass($className);
            $constructor = $reflection->getConstructor();

            if (!$constructor) {
                array_pop($resolvingStack);
                return $reflection->newInstance();
            }

            // Recursively resolve dependencies for the constructor
            $dependencies = $this->resolveDependencies($constructor->getParameters());
            array_pop($resolvingStack);
            return $reflection->newInstanceArgs($dependencies);

        } catch (\ReflectionException $e) {
            array_pop($resolvingStack);
            throw new RuntimeException("Cannot autowire {$className}: " . $e->getMessage());
        }
    }

    /**
     * Handles errors and exceptions.
     *
     * @param \Throwable $e The exception to handle.
     */
    private function handleError(\Throwable $e): void
    {
        $code = $e->getCode();
        $statusCode = in_array($code, [400, 401, 403, 404, 405, 500, 503]) ? $code : 500;

        // Try to render an error view if the view service is available
        if ($this->has('view')) {
            try {
                $view = $this->resolve('view');
                $viewsPath = $this->config['template']['views'] ?? '';
                $errorView = $viewsPath . '/errors/' . $statusCode . '.php';

                if (file_exists($errorView)) {
                    http_response_code($statusCode);
                    $view->render('errors/' . $statusCode . '.php', [
                        'code' => $statusCode,
                        'message' => $e->getMessage(),
                    ]);
                    return;
                }
            } catch (\Throwable $viewError) {
                // View rendering failed, fall through to plain text
            }
        }

        // Fallback: plain text response
        /** @var \Fastpress\Http\Response $response */
        $response = $this->resolve('response');
        $response->setStatusCode($statusCode)
            ->setContent($statusCode === 404 ? 'Not Found' : 'Server Error')
            ->send();
    }

    /**
     * Sets a configuration value.
     *
     * @param string $key   The configuration key (supports 'section:key' notation).
     * @param mixed  $value The configuration value.
     * @return self
     */
    public function set(string $key, mixed $value): self
    {
        if (str_contains($key, ':')) {
            [$section, $subKey] = explode(':', $key, 2);
            $this->config[$section] = array_merge(
                $this->config[$section] ?? [],
                [$subKey => $value]
            );
        } else {
            $this->config[$key] = $value;
        }
        return $this;
    }

    /**
     * Checks if a configuration key exists.
     *
     * @param mixed $offset The configuration key.
     * @return bool True if the key exists, false otherwise.
     */
    public function offsetExists($offset): bool
    {
        return isset($this->config[$offset]);
    }

    /**
     * Gets a configuration value.
     *
     * @param mixed $offset The configuration key.
     * @return mixed The configuration value.
     */
    public function offsetGet($offset): mixed
    {
        return $this->config[$offset] ?? null;
    }

    /**
     * Sets a configuration value.
     *
     * @param mixed $offset The configuration key.
     * @param mixed $value  The configuration value.
     */
    public function offsetSet($offset, $value): void
    {
        $this->config[$offset] = $value;
    }

    /**
     * Unsets a configuration value.
     *
     * @param mixed $offset The configuration key.
     */
    public function offsetUnset($offset): void
    {
        unset($this->config[$offset]);
    }

    /**
     * Gets a configuration value using dot notation.
     *
     * @param string|null $path The configuration path (e.g., 'database.mysql.host').
     * @return mixed The configuration value.
     */
    public function getConfig(string $path = null): mixed
    {
        if ($path === null) {
            return $this->config;
        }

        $parts = explode('.', $path);
        $data = $this->config;

        foreach ($parts as $part) {
            if (!isset($data[$part])) {
                return null;
            }
            $data = $data[$part];
        }

        return $data;
    }
}
