<?php

session_start(); 

require __DIR__ . "/../../vendor/autoload.php"; 
// create another configuration file for production, e.g. conf.prod.php
require __DIR__ . "/conf.dev.php"; 
$app = new Fastpress\Application($conf);


$app->get('/', 'DefaultController@index'); 

// Define routes and their handlers:

$app->run();
