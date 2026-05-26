<?php
/**
 * Production configuration.
 *
 * Copy this file and customize for your production environment.
 * Access configs with $conf['name']['key'] in your application.
 * Override any setting with $app->set('name:key', 'value');
 */

$conf['host'] = 'https://your-domain.com';

// default page metadata
$conf['page'] = [
   'title'        => 'Your Site Title | Tagline',
   'keywords'     => 'your, site, keywords',
   'description'  => 'Your site description here',
   'image'        => $conf['host'] . '/images/thumbnail.png'
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
   'mysql' => [
       'driver' => 'mysql',
       'host' => 'your-database-host',
       'port' => 3306,
       'username' => 'your-database-username',
       'password' => 'your-database-password',
       'database' => 'your-database-name',
       'charset' => 'utf8mb4',
       'collation' => 'utf8mb4_unicode_ci',
       'prefix' => '',
   ],
];

// session security configs for production
$conf['session'] = [
   'cookie_path' => '/',
   'cache_expire' => 180,
   'cookie_secure' => true, // Requires HTTPS
   'cache_limiter' => 'nocache',
   'hash_function' => 'sha256',
   'cookie_domain' => '',
   'gc_maxlifetime' => 1440,
   'cookie_lifetime' => 0,
   'cookie_httponly' => true,
   'cookie_samesite' => 'Lax',
   'use_only_cookies' => 1,
   'use_strict_mode' => 1,
   'sid_length' => 48,
   'sid_bits_per_character' => 6,
   'use_trans_sid' => 0,
];

// define your services
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
  'session' => function($container) {
      return new Fastpress\Security\Session(
          $container->getConfig('session') ?? []
      );
  },
  'view' => function($container) {
      return new Fastpress\Presentation\View(
          $container,
          $container->resolve('session')
      );
  },
  'database' => function($container) {
      return new Fastpress\Memory\Database(
         $container->getConfig('database.mysql')
      );
  }
];

// Do NOT display errors in production
error_reporting(0);
ini_set('display_errors', '0');
