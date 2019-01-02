<?php

namespace MCServerStatus\Tests\Features;

use MCServerStatus\MCPing;
use PHPUnit\Framework\TestCase;

class MCPingTest extends TestCase {
	
	protected function setUp() {
		parent::setUp();
	}
	
	public function testClass(){
		$response=MCPing::check('us.mineplex.com');
		$this->assertTrue(is_array($response->toArray()));
	}
	
}
