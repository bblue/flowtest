<?php
use bblue\ruby\Component\Container\Container;

/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 28.01.2016
 * Time: 11:50
 */
class ContainerTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $container = new Container();
        $this->assertSame($container, $container->get('container'), '__construct() automatically registers itself');
    }

    public function testRegisterClassWithoutAlias()
    {
        $class = new stdClass();
        $this->container->register($class);
        $this->assertTrue($this->container->has('stdClass'));
    }
}
