<?php
/**
 * This file contains the default settings for new sites.
 * Customize these settings for production environment.
 *
 * Access configs with $conf['name']['key'] in your application.
 * Override any setting with $app->set('name:key', 'value');
 * Add any arbitrary config, like asset inclusion, with $conf['include'] = ['jQuery' => true];
 */

$conf['host'] = 'http://localhost';

// default page metadata
$conf['page'] = [
   'title'        => 'Your Site Title | Tagline',
   'keywords'     => 'your, site, keywords',
   'description'  => 'Your site description here',
   'image'        => $conf['host'] . '/images/thumbnail.png'
];

// database details for production
$conf['database'] = [
   'driver'     => 'mysql',
   'host'       => 'your-database-host',
   'database'   => 'your-database-name',
   'username'   => 'your-database-username',
   'password'   => 'your-database-password',
   'charset'    => 'utf8',
   'collation'  => 'utf8_unicode_ci',
   'prefix'     => 'your-table-prefix',
   'options'    => [
      PDO::ATTR_EMULATE_PREPARES => false,
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
   ]
];

$conf['namespaces'] = [
   'controller' => 'App\\Controller\\',
   'model' => 'App\\Model\\'
];

// path to views and layouts
$conf['template'] = [
   'views'  => __DIR__ . '/view/',
   'layout' => __DIR__ . '/view/layout/'
];

// path to your assets
$conf['assets'] = [
   'root'  => $conf['host'] . '/assets', 
   'js'   => $conf['host'] . '/assets/js',
   'css'  => $conf['host'] . '/assets/css',
   'img'  => $conf['host'] . '/assets/img',
];

// Database configuration
$conf['database'] = [
   // Default database configuration
   'mysql' => [
       'driver' => 'mysql',
       'host' => 'localhost',
       'port' => 3306, // Default MySQL port. Change it according to the database system.
       'username' => 'root',
       'password' => '',
       'database' => 'your-database-name',
       'charset' => 'utf8',
       'collation' => 'utf8_unicode_ci',
       'prefix' => '', // Table prefix if any
       // Add any other database-specific parameters here
   ],
];

// session security configurations for production
// session security configs
$conf['session'] = [
   'strict' => true, // Enforces strict mode, preventing uninitialized session IDs
   'cookie_path' => '/', // Limits the path where the cookie is valid
   'cache_expire' => 180, // Sets the cache expire time in minutes
   'cookie_secure' => true, // Ensures cookies are sent over secure connections
   'cache_limiter' => 'nocache', // Prevents caching of session pages
   'hash_function' => 'sha256', // Uses SHA-256 for session ID generation
   'cookie_domain' => '', // Sets the domain where the cookie is valid
   'referer_check' => '', // Referrer check for extra security (set as needed)
   'gc_maxlifetime' => 1440, // Sets the session garbage collection max lifetime
   'cookie_lifetime' => 0, // 0 means "until the browser is closed"
   'cookie_httponly' => true, // Prevents JavaScript access to session cookies
   'use_only_cookies' => 1, // Ensures session ID is stored in cookies, not in URLs
   'session.sid_length' => 64, // Length of session ID string
   'session.sid_bits_per_character' => 5, // Bits per character in session ID (more bits = more entropy)
   'session.use_trans_sid' => 0, // Prevents transparent session ID management (better for security)
   'session.cookie_samesite' => 'Lax' // Controls whether cookies are sent with cross-site requests
];

// define your services like router, request, response, session, view, etc.
$conf['services'] = [
  'router' => function() {
      return new Fastpress\Routing\Router();
  },
  'request' => function() {
      return new Fastpress\Http\Request($_GET, $_POST, $_SERVER, $_COOKIE);
  },
  'response' => function() {
      return new Fastpress\Http\Response();
  },
  'session' => function() {
      return new Fastpress\Security\Session();
  },
  'view' => function($container) {  
      return new Fastpress\Presentation\View(
          $container, 
          $container->resolve('session')
      );
  },
   'database' => function($conf) {
      return new Fastpress\Memory\Database(
         $conf['database']['mysql'],
      );
   }
];

// Define other configurations such as cache settings, blocks, use flags, etc.
$conf['block'] = [
   'header'   => true,
   'content'  => true,
   'sidebar'  => true, 
   'footer'   => true, 
];


// misc directives 
$conf['use'] = [
   'output_buffering' => false, 
   'template_inheritance' => false, 
   'adsense' => false, 
   'facebook_api' => false, 
];





// Report all PHP errors
error_reporting(E_ALL);

// Set the display_errors directive to 'On' in php.ini to display errors
ini_set('display_errors', '1');