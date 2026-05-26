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
       'host' => 'localhost',
       'port' => 3306,
       'username' => 'root',
       'password' => '',
       'database' => 'your-database-name',
       'charset' => 'utf8mb4',
       'collation' => 'utf8mb4_unicode_ci',
       'prefix' => '',
   ],
];

// session security configs
$conf['session'] = [
   'cookie_path' => '/',
   'cache_expire' => 180,
   'cookie_secure' => false, // Set to true in production (requires HTTPS)
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
  'session' => function() {
      return new Fastpress\Security\Session();
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

// Define other configurations such as blocks, use flags, etc.
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
];

// Report all PHP errors in dev
error_reporting(E_ALL);
ini_set('display_errors', '1');
