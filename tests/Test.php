<?php

/**
 * Bootstrap test enviroment
 */
$test =  new Test;

/**
 * Initial test class for experimenting with PHPUnit
 */
class Test extends PHPUnit_Framework_TestCase
{
	public function testHello()
	{
	    $this->assertEquals(1,1);
	}
}
