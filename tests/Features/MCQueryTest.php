<?php

namespace MCServerStatus\Tests\Features;

use MCServerStatus\MCQuery;
use PHPUnit\Framework\TestCase;

class MCQueryTest extends TestCase {
	
	protected function setUp() {
		parent::setUp();
	}
	
	public function testClass(){
		$response=MCQuery::check('play.moonforce.eu',19132);
		$this->assertTrue(is_array($response->toArray()));
	}
	
}
