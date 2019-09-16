<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Fastpress\Http\Response; 

class ResponseTest extends TestCase
{
	public function setup(){
		$this->response = new Response;
	}

	// set HTTP response code and test
	public function testSetResponseCode(){
		$this->response->setCode(301);
		$this->assertEquals($this->response->getCode(), 301);
	}

}


// namespace Torpedo\Test;
// use Torpedo\Response;

// class ResponseTest extends \PHPUnit_Framework_TestCase {

// 	public $response; 

// 	public function setup(){
// 		$this->response = new \Torpedo\Http\Response; 
// 	}

// 	// set HTTP response code and test
// 	public function testSetResponseCode(){
// 		$this->response->setCode(301); 
// 		$this->assertEquals($this->response->getCode(), 301);
// 	}
	
// 	// set HTTP code and text and test
// 	public function testSetResponseCodeAndText(){
// 		$this->response->setResponse(400, 'Four Hundred'); 
// 		$this->assertEquals($this->response->getCode(), 400);
// 		$this->assertEquals($this->response->getText(), 'Four Hundred');
// 	}
	
// 	// set a redirect command and test code/location separately
// 	public function testRedirectLocationAndCode(){
// 		$this->response->redirect('/foo', 301);
// 		$this->assertEquals($this->response->getCode(), 301);
// 		$this->assertContains('/foo', $this->response->getHeaders()); 
// 	}

// 	/**
// 	* use the exception anotation to test an exception triggered by invalid 
// 	* HTTP code value
// 	* @expectedException \LogicException
// 	* @expectedExceptionMessage 987654321 is unsuported HTTP status code
// 	*/
// 	public function testSetStatusThrowsLogicExceptionOnInvalidStatusCodeRange(){
// 		$this->response->setCode(987654321);
// 	}

// }