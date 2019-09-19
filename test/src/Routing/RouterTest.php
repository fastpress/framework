<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Fastpress\Routing\Router; 

class RouterTest extends TestCase
{
	protected $router; 
	protected $server = [
		'SERVER_PROTOCOL' => '1.0',
		'REQUEST_METHOD' => 'GET',
		'REQUEST_URI' => '/blog',
		'REQUEST_URI_PATH' => '/index.php',
		'HTTPS' => FALSE,
		'HTTP_MY_HEADER' => 'my value'
	];


	public function setup () {
		$this->router = new Router();
	}
	public function testGetRequest(){
		$this->router->get('/', 'bar');
		$this->assertContains('bar', $this->router->routes['GET'][0]);
	}


}


// namespace Torpedo\Test;
// use \Torpedo\Routing;

// class RouterTest extends \PHPUnit_Framework_TestCase {
// 	protected $server = [
// 		'SERVER_PROTOCOL' => '1.0',
// 		'REQUEST_METHOD' => 'GET',
// 		'REQUEST_URI' => '/blog',
// 		'REQUEST_URI_PATH' => '/index.php',
// 		'HTTPS' => FALSE,
// 		'HTTP_MY_HEADER' => 'my value'
// 	];

// 	protected $form = [
		
// 	];

// 	public function setup(){
// 		$this->router = new \Torpedo\Routing\Router; 
// 	}

// 	public function testGetRequest(){
// 		$this->router->get('/', 'bar'); 
// 		$this->assertContains('bar', $this->router->routes['GET'][0]);	
// 	}

// 	public function testPostRequest(){
// 		$this->router->post('/', 'tar'); 
// 		$this->assertContains('tar', $this->router->routes['POST'][0]);	
// 	}

// 	public function testAnyRequest(){
// 		$this->router->any('/', 'samayo'); 
// 		$this->assertContains('samayo', $this->router->routes['GET'][0]);
// 		$this->assertContains('samayo', $this->router->routes['POST'][0]);
// 		$this->assertContains('samayo', $this->router->routes['PUT'][0]);	
// 		$this->assertContains('samayo', $this->router->routes['DELETE'][0]);
// 	}

// 	public function testPutRequest(){
// 		$this->router->put('/', 'atl'); 
// 		$this->assertContains('atl', $this->router->routes['PUT'][0]);	
// 	}

// 	public function testDeleteRequest(){
// 		$this->router->delete('/', 'ben'); 
// 		$this->assertContains('ben', $this->router->routes['DELETE'][0]);	
// 	}


// 	public function testRouteBasedController(){
// 		$server = $this->server; 
// 		$post = $this->form; 
// 		$this->router->get('/blog', 'fooController@indexAction');
// 		$result = $this->router->match($server, $post); 	
// 		$this->assertInternalType('array', $result);
// 		$this->assertContains('fooController', $result);
// 		$this->assertContains('indexAction', $result);
// 	}

// 	public function testGETRoutesWithNamedArgs(){
// 		$server = [
// 			'REQUEST_URI' => '/user/simon',
// 			'REQUEST_METHOD' => 'GET'
// 		]; 
// 		$post = $this->form; 
// 		$this->router->get('/user/{:name}', 'userController@profiles');
// 		$result = $this->router->match($server, $post); 	
// 		$this->assertInternalType('array', $result);
// 		$this->assertContains('userController', $result);
// 		$this->assertContains('profiles', $result);
// 		$this->assertContains([0 => 'simon'], $result);
// 	}

// 	public function testPOSToutesWithNamedArgs(){
// 		$server = [
// 			'REQUEST_URI' => '/article/ronpaul',
// 			'REQUEST_METHOD' => 'POST'
// 		]; 
// 		$post = $this->form; 
// 		$this->router->post('/article/{:slug}', 'barController@barAction');
// 		$result = $this->router->match($server, $post); 	
// 		$this->assertInternalType('array', $result);
// 		$this->assertContains('barController', $result);
// 		$this->assertContains('barAction', $result);
// 		$this->assertContains([0 => 'ronpaul'], $result);
// 	}

// 	public function testPUToutesWithNamedArgs(){
// 		$server = [
// 			'REQUEST_URI' => '/article/ronpaul/1',
// 			'REQUEST_METHOD' => 'GET'
// 		]; 
// 		$post = ['_method' => 'PUT']; 
// 		$this->router->put('/article/ronpaul/{:id}', 'ArticleController@article');
// 		$result = $this->router->match($server, $post); 	
// 		$this->assertInternalType('array', $result);
// 		$this->assertContains('ArticleController', $result);
// 		$this->assertContains('article', $result);
// 		$this->assertContains([0 => 1], $result);
// 	}
// 	public function testNamedArgsPassedViaFunctionParamsMatch(){
// 		$server = [
// 			'REQUEST_URI' => '/user/simon/age/11/url/i-am-slug',
// 			'REQUEST_METHOD' => 'GET'
// 		]; 
// 		$post = $this->form; 

// 		$this->router->get('/user/{:name}/age/{:id}/url/{:slug}', 
// 			function($name, $id, $slug){
// 				return [$name, $id, $slug];
// 			});

// 			$result = $this->router->match($server, $post); 
// 			$this->assertInternalType('array', $result);
// 			$this->assertContains('simon', $result);
// 			$this->assertContains('11', $result);
// 			$this->assertContains('i-am-slug', $result);
// 			$this->assertEquals(['simon', 11, 'i-am-slug'], $result);
// 		}
	

// 	public function testCalableRouteEvaluatesCallableArguments(){
// 		$server = [
// 			'REQUEST_URI' => '/article/ronpaul/1',
// 			'REQUEST_METHOD' => 'GET'
// 		]; 
// 		$post = ['_method' => 'POST']; 
// 		$this->router->any('/article/ronpaul/{:id}', function(){
			
// 		});

// 		$result = $this->router->match($server, $post); 
// 		$this->assertEquals(null, $result);
// 	}
// }