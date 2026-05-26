# Fastpress Framework - AI Reference

This is the complete API reference for Fastpress, a PHP micro-framework.
Use this document when building applications with Fastpress.

## Installation

```bash
composer require fastpress/framework
```

## Project Structure

```
project/
  composer.json        # Must include App\ PSR-4 autoloading (see below)
  public/
    index.php          # Entry point: requires app/routes.php
    .htaccess          # URL rewriting to index.php
  app/
    routes.php         # Bootstrap: autoloader, config, Application, route definitions
    dev.config.php     # Configuration (services, DB, session, template paths)
    controller/        # App\Controller\ namespace
    service/           # App\Service\ namespace
    repository/        # App\Repository\ namespace
    view/              # Template files (.php or .html)
      layout/          # Layout templates
      errors/          # Error pages (404.php, 500.php)
```

### composer.json must include App autoloading:

```json
{
    "require": { "fastpress/framework": "^0.1" },
    "autoload": {
        "psr-4": {
            "App\\Controller\\": "app/controller/",
            "App\\Service\\": "app/service/",
            "App\\Repository\\": "app/repository/"
        }
    }
}
```

### public/index.php:

```php
<?php
require __DIR__ . '/../app/routes.php';
```

### app/routes.php:

```php
<?php
require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/dev.config.php";

$app = new Fastpress\Application($conf);

// Define routes
$app->get('/', 'HomeController@index');
$app->get('/user/{:id}', 'UserController@show');
$app->post('/user', 'UserController@store');
$app->put('/user/{:id}', 'UserController@update');
$app->delete('/user/{:id}', 'UserController@destroy');
$app->patch('/user/{:id}', 'UserController@patch');
$app->any('/webhook', 'WebhookController@handle');

$app->run();
```

## Routing

Routes are defined on the Application instance. The handler format is `'ControllerName@method'`.
The controller namespace prefix comes from `$conf['namespaces']['controller']`.

### Route parameters

Parameters use `{name}` or `{:name}` syntax. The `:` prefix maps to built-in type patterns:

| Syntax | Pattern | Matches |
|--------|---------|---------|
| `{:id}` or `{id}` | `\d+` | Digits only |
| `{:slug}` or `{slug}` | `[a-z0-9\-]+` | Lowercase alphanumeric + hyphens |
| `{:name}` or `{name}` | `[a-zA-Z]+` | Letters only |
| `{:alpha}` | `[a-zA-Z]+` | Letters only |
| `{:alphanum}` | `[a-zA-Z0-9]+` | Letters and digits |
| `{:num}` | `[0-9]+` | Digits only |
| `{:uuid}` | UUID v4 pattern | UUIDs |
| `{:any}` or `{any}` | `[^/]+` | Anything except `/` |
| `{custom}` | `[^/]+` | Fallback for unrecognized names |

### Route groups

```php
$router = $app->resolve('router');
$router->group(['prefix' => '/api', 'middleware' => ['App\Middleware\Auth']], function($router) {
    $router->get('/users', 'UserController@index');
    $router->get('/users/{:id}', 'UserController@show');
    $router->post('/users', 'UserController@store');
});
```

### Named routes

```php
$router->get('/user/{:id}', 'UserController@show')->name('user.show');
// Generate URL:
$url = $router->getNamedRoute('user.show', ['id' => 42]); // "/user/42"
```

### Middleware on routes

```php
$router->get('/admin', 'AdminController@index');
$router->middleware('App\Middleware\Auth');

// Or inline callable:
$router->get('/debug', 'DebugController@index');
$router->middleware(function($request, $response, $next) {
    if (!$request->param('debug')) {
        $response->setStatusCode(403)->setContent('Forbidden')->send();
        return;
    }
    $next();
});
```

## Controllers

Controllers live in `App\Controller\` namespace. Dependencies are autowired via constructor and method injection.
Route parameters are injected by name into method arguments.

```php
<?php
namespace App\Controller;

use Fastpress\Http\Request;
use Fastpress\Http\Response;
use Fastpress\Presentation\View;
use Fastpress\Security\Session;
use App\Service\UserService;

class UserController
{
    private UserService $userService;

    // Constructor dependencies are autowired
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    // Method dependencies are autowired. Route params injected by name.
    // For route '/user/{:id}', the $id parameter is injected automatically.
    public function show(int $id, Request $request, View $view, Session $session)
    {
        $user = $this->userService->findById($id);
        $view->render('user/show.php', ['user' => $user]);
    }

    public function store(Request $request, Response $response, Session $session)
    {
        $errors = $request->validate([
            'email' => 'required|email',
            'name' => 'required|min:2',
            'password' => 'required|min:8',
        ]);

        if (!empty($errors)) {
            echo $response->json(['errors' => $errors], 422);
            return;
        }

        $this->userService->create(
            $request->input('email'),
            $request->input('name'),
            $request->input('password')
        );

        $session->setFlash('success', 'User created!', 'users');
        $response->redirect('/users');
    }

    // API endpoint returning JSON
    public function apiIndex(Response $response)
    {
        $users = $this->userService->listAll();
        $response->json(['data' => $users])->send();
    }
}
```

## Services

Services contain business logic. They are autowired when type-hinted in controllers.

```php
<?php
namespace App\Service;

use App\Repository\UserRepository;

class UserService
{
    private UserRepository $repo;

    public function __construct(UserRepository $repo)
    {
        $this->repo = $repo;
    }

    public function findById(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function authenticate(string $email, string $password): array
    {
        $user = $this->repo->findByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            return ['result' => true, 'data' => $user['id']];
        }
        return ['result' => false];
    }
}
```

## Repositories

Repositories handle data access via the Database class.

```php
<?php
namespace App\Repository;

use Fastpress\Memory\Database;

class UserRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?array
    {
        return $this->db->select('users', ['*'], ['id' => $id]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->select('users', ['*'], ['email' => $email]);
    }
}
```

## Request API (`Fastpress\Http\Request`)

Constructor: `new Request(?array $get, ?array $post, ?array $server, ?array $cookies, ?array $files)`

### Input

```php
$value = $request->input('key');                    // Auto-detects GET/POST/JSON, sanitized
$value = $request->input('key', 'default');          // With default
$value = $request->input('key', null, false);        // Raw (no sanitization)
$name = $request->get('name');                       // GET parameter
$email = $request->post('email');                    // POST parameter
$data = $request->json('key');                       // JSON body key
$all = $request->all();                              // All GET + POST + JSON merged
$body = $request->getBody();                         // Raw request body
```

### Route parameters

```php
$id = $request->param('id');                         // Route parameter value
$id = $request->param('id', 0);                      // With default
```

### Request info

```php
$method = $request->getMethod();                     // GET, POST, PUT, DELETE, PATCH
$request->isGet();                                   // bool
$request->isPost();                                  // bool
$request->isPut();                                   // bool
$request->isDelete();                                // bool
$request->isPatch();                                 // bool
$request->isAjax();                                  // bool (X-Requested-With check)
$request->isJson();                                  // bool (Content-Type check)
$ip = $request->getIp();                             // Client IP (REMOTE_ADDR)
$uri = $request->getUri();                           // Full URI with query string
$path = $request->getPath();                         // Path without query string
$query = $request->getQueryString();                 // Query string only
$fullUrl = $request->getFullUrl();                   // scheme + host + URI
$scheme = $request->getScheme();                     // http or https
$host = $request->getHost();                         // Hostname
$token = $request->bearerToken();                    // Bearer token from Authorization header
$accepts = $request->accepts('application/json');    // Content negotiation
$header = $request->header('X-Custom');              // Any header value
```

### Files

```php
$file = $request->file('avatar');                    // File upload info array
$hasFile = $request->hasFile('avatar');               // Upload exists and no error
```

### Cookies

```php
$hasCookie = $request->hasCookie('token');            // bool
$value = $request->cookie('token');                  // Cookie value
```

### Validation

```php
$errors = $request->validate([
    'email' => 'required|email',
    'name' => 'required|min:2|max:100',
    'password' => 'required|min:8',
]);
// Returns empty array if valid, or ['field' => 'error message'] if invalid
// Available rules: required, email, min:N, max:N
```

## Response API (`Fastpress\Http\Response`)

### Basic responses

```php
$response->setStatusCode(200);                       // Set HTTP status
$response->setContent('Hello');                      // Set body
$response->send();                                   // Send headers + body

// Chained:
$response->setStatusCode(201)->setContent('Created')->send();
```

### JSON responses

```php
$response->json(['key' => 'value'])->send();           // 200 JSON
$response->json(['key' => 'value'], 201)->send();      // 201 JSON
$response->withError('Not found', 404)->send();        // {"error":true,"message":"Not found"}
$response->withSuccess($data, 'Done')->send();         // {"success":true,"message":"Done","data":...}
```

### Headers

```php
$response->header('X-Custom', 'value');              // Single header
$response->headers([                                 // Multiple headers
    'X-Custom' => 'value',
    'Cache-Control' => 'no-cache',
]);
$response->noCache();                                // Anti-cache headers
```

### Redirects

```php
$response->redirect('/dashboard');                   // 302 redirect
$response->redirect('/login', 301);                  // 301 redirect
$response->back();                                   // Redirect to HTTP_REFERER
$response->back('/fallback');                        // With fallback URL
```

### Cookies

```php
$response->setCookie('name', 'value', time() + 3600);  // Set cookie
$response->clearCookie('name');                         // Delete cookie
```

### File downloads

```php
$response->download('/path/to/file.pdf', 'document.pdf');
```

### Streaming

```php
$response->stream(function($bufferSize) {
    // Return data chunks, return false/null to stop
    return fread($handle, $bufferSize);
});
```

## View API (`Fastpress\Presentation\View`)

### Rendering

```php
$view->render('user/profile.php', ['name' => $name, 'email' => $email]);
```

### Template with layout (view file):

```php
<?php $this->extend('layout') ?>

<?php $this->block('content') ?>
    <h1>Page Title</h1>
    <?php $this->set('page.title', 'My Page') ?>

    <?php if (isset($name)): ?>
        <p>Hello, <?= $this->e($name) ?></p>
    <?php endif; ?>
<?php $this->endBlock() ?>
```

### Layout file (app/view/layout/layout.html):

```html
<!DOCTYPE html>
<html>
<head>
    <title><?= $this->config('page.title') ?? 'Default Title' ?></title>
</head>
<body>
    <?php $this->content('content') ?>
</body>
</html>
```

### View methods available inside templates

```php
$this->extend('layout');                             // Use layout
$this->block('name');                                // Start content block
$this->endBlock();                                   // End content block
$this->content('name');                              // Render block (in layout)
$this->e($value);                                    // HTML-escape output
$this->set('page.title', 'value');                   // Set config/meta value
$this->config('page.title');                         // Read config value
$this->session();                                    // Access session object
$this['session']->get('key');                        // ArrayAccess to session
$this['session']->hasFlash('success', 'type');       // Check flash messages
```

## Session API (`Fastpress\Security\Session`)

```php
// Basic operations
$session->set('user_id', 42);                        // Set value
$session->get('user_id');                            // Get value
$session->get('key', 'default');                     // Get with default
$session->has('user_id');                            // Check existence
$session->delete('user_id');                         // Remove key
$session->all();                                     // All user data (excludes internal keys)
$session->clear();                                   // Remove all data
$session->destroy();                                 // Destroy session completely

// CSRF protection
$token = $session->token();                          // Get/generate CSRF token
$valid = $session->validateToken($submittedToken);   // Validate CSRF token
$html = $session->csrfField();                       // <input type="hidden" name="_csrf" value="...">

// Flash messages
$session->setFlash('success', 'Item saved!', 'items');  // Set flash (key, value, type)
$message = $session->getFlash('success');               // Get and consume flash
$exists = $session->hasFlash('success', 'items');        // Check flash exists with type
```

## Database API (`Fastpress\Memory\Database`)

Constructor: `new Database(['host' => '...', 'port' => 3306, 'database' => '...', 'username' => '...', 'password' => '...', 'charset' => 'utf8mb4'])`

### CRUD

```php
// SELECT single row (returns array or null)
$user = $db->select('users', ['id', 'name', 'email'], ['id' => 1]);

// SELECT multiple rows
$users = $db->selectAll('users', ['*'], ['status' => 'active']);

// SELECT with ordering, limit, offset
$users = $db->selectAll('users', ['*'], ['status' => 'active'], 'created_at DESC', 10, 0);

// COUNT
$total = $db->count('users', ['status' => 'active']);

// INSERT (returns last insert ID)
$id = $db->insert('users', ['name' => 'John', 'email' => 'john@example.com']);

// UPDATE (returns affected row count)
$affected = $db->update('users', ['name' => 'Jane'], ['id' => 1]);

// DELETE (returns affected row count)
$deleted = $db->delete('users', ['id' => 1]);
```

### Raw queries

```php
$stmt = $db->query("SELECT * FROM users WHERE age > :age", ['age' => 18]);
$rows = $stmt->fetchAll();
```

### Transactions

```php
$db->beginTransaction();
try {
    $db->insert('orders', ['user_id' => 1, 'total' => 99.99]);
    $db->update('users', ['order_count' => 1], ['id' => 1]);
    $db->commit();
} catch (\Exception $e) {
    $db->rollBack();
    throw $e;
}
```

### Debug

```php
$queries = $db->getQueries();  // Array of all SQL strings executed
```

## Middleware

Middleware classes must have a `handle(Request $request, Response $response, Closure $next)` method.

```php
<?php
namespace App\Middleware;

use Fastpress\Http\Request;
use Fastpress\Http\Response;
use Closure;

class Auth
{
    public function handle(Request $request, Response $response, Closure $next): void
    {
        $session = new \Fastpress\Security\Session();
        if (!$session->has('user_id')) {
            $response->redirect('/login');
            return; // Stop pipeline — do NOT call $next()
        }
        $next(); // Continue to controller
    }
}
```

### Built-in: CORS Middleware

```php
use Fastpress\Middleware\Cors;

// Apply to API routes:
$router->group(['prefix' => '/api', 'middleware' => [Cors::class]], function($router) {
    $router->get('/data', 'ApiController@data');
});
```

OPTIONS preflight requests return 204 automatically before route matching.

## Configuration

All configuration is in a `$conf` array. Key sections:

```php
$conf['host'] = 'http://localhost:8000';
$conf['namespaces'] = ['controller' => 'App\\Controller\\'];
$conf['template'] = [
    'views'  => __DIR__ . '/view/',
    'layout' => __DIR__ . '/view/layout/',
];
$conf['database'] = [
    'mysql' => [
        'host' => 'localhost', 'port' => 3306,
        'database' => 'mydb', 'username' => 'root', 'password' => '',
        'charset' => 'utf8mb4',
    ],
];
$conf['session'] = [
    'cookie_secure' => false,  // true in production (requires HTTPS)
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => 1,
];
```

### Accessing config

```php
// From controller (via Application):
$app->config();                          // Full config array
$app->getConfig('database.mysql.host');  // Dot-notation access
$app->set('page:title', 'My Title');     // Set via colon notation

// From view:
$this->config('page.title');             // Dot-notation access
$this->set('page.title', 'My Title');    // Supports both : and . separators
```

## Error Pages

Create `app/view/errors/404.php` and `app/view/errors/500.php`. They receive `$code` and `$message` variables.

```php
<?php $this->extend('layout') ?>
<?php $this->block('content') ?>
    <h1>Error <?= $this->e($code) ?></h1>
    <p><?= $this->e($message) ?></p>
<?php $this->endBlock() ?>
```

If no error template exists, plain text responses are sent.

## Services Registration

Services are defined in the config and resolved via the container:

```php
$conf['services'] = [
    'router' => fn() => new Fastpress\Routing\Router(),
    'request' => fn() => new Fastpress\Http\Request($_GET, $_POST, $_SERVER, $_COOKIE),
    'response' => fn() => new Fastpress\Http\Response(),
    'session' => fn($c) => new Fastpress\Security\Session($c->getConfig('session') ?? []),
    'view' => fn($c) => new Fastpress\Presentation\View($c, $c->resolve('session')),
    'database' => fn($c) => new Fastpress\Memory\Database($c->getConfig('database.mysql')),
];
```

Access any service: `$app->resolve('serviceName')`

Register custom services: `$app->register('mailer', fn() => new Mailer())`

## Complete Feature Walkthrough: Login System

### Route:
```php
$app->get('/login', 'AuthController@loginForm');
$app->post('/login', 'AuthController@login');
$app->get('/logout', 'AuthController@logout');
```

### Controller:
```php
<?php
namespace App\Controller;

use Fastpress\Http\Request;
use Fastpress\Http\Response;
use Fastpress\Presentation\View;
use Fastpress\Security\Session;
use App\Service\AuthService;

class AuthController
{
    private AuthService $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    public function loginForm(View $view)
    {
        $view->render('auth/login.php');
    }

    public function login(Request $request, Response $response, Session $session)
    {
        $errors = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (!empty($errors)) {
            $session->setFlash('error', 'Please fix the errors below', 'login');
            $response->redirect('/login');
            return;
        }

        $result = $this->auth->attempt(
            $request->input('email'),
            $request->input('password')
        );

        if ($result['result']) {
            $session->set('user_id', $result['data']);
            $response->redirect('/dashboard');
        } else {
            $session->setFlash('error', 'Invalid credentials', 'login');
            $response->redirect('/login');
        }
    }

    public function logout(Session $session, Response $response)
    {
        $session->destroy();
        $response->redirect('/login');
    }
}
```

### View (app/view/auth/login.php):
```php
<?php $this->extend('layout') ?>
<?php $this->set('page.title', 'Login') ?>

<?php $this->block('content') ?>
    <?php if ($this['session']->hasFlash('error', 'login')): ?>
        <div class="error"><?= $this->e($this['session']->getFlash('error')) ?></div>
    <?php endif; ?>

    <form method="POST" action="/login">
        <?= $this->session()->csrfField() ?>
        <input type="email" name="email" required>
        <input type="password" name="password" required>
        <button type="submit">Login</button>
    </form>
<?php $this->endBlock() ?>
```
