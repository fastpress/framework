<?php

declare(strict_types=1);

namespace Fastpress\Middleware;

use Fastpress\Http\Request;
use Fastpress\Http\Response;
use Closure;

/**
 * CORS middleware for handling Cross-Origin Resource Sharing.
 *
 * Usage in routes:
 *   $router->get('/api/data', 'ApiController@index');
 *   $router->middleware(Fastpress\Middleware\Cors::class);
 *
 * Or with a group:
 *   $router->group(['prefix' => '/api', 'middleware' => [Fastpress\Middleware\Cors::class]], function($router) {
 *       $router->get('/users', 'ApiController@users');
 *   });
 */
class Cors
{
    private string $allowedOrigins;
    private string $allowedMethods;
    private string $allowedHeaders;
    private int $maxAge;

    public function __construct(
        string $allowedOrigins = '*',
        string $allowedMethods = 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
        string $allowedHeaders = 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN',
        int $maxAge = 86400
    ) {
        $this->allowedOrigins = $allowedOrigins;
        $this->allowedMethods = $allowedMethods;
        $this->allowedHeaders = $allowedHeaders;
        $this->maxAge = $maxAge;
    }

    /**
     * Handle the request.
     *
     * @param Request  $request  The HTTP request.
     * @param Response $response The HTTP response.
     * @param Closure  $next     The next middleware/action in the pipeline.
     */
    public function handle(Request $request, Response $response, Closure $next): void
    {
        $response->headers([
            'Access-Control-Allow-Origin' => $this->allowedOrigins,
            'Access-Control-Allow-Methods' => $this->allowedMethods,
            'Access-Control-Allow-Headers' => $this->allowedHeaders,
            'Access-Control-Max-Age' => (string) $this->maxAge,
        ]);

        // Handle preflight OPTIONS requests
        if ($request->getMethod() === 'OPTIONS') {
            $response->setStatusCode(204)->send();
            return;
        }

        $next();
    }
}
