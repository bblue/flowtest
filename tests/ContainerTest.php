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
    /**
     * @var Container
     */
    public $container;

    public function __construct()
    {
        $this->container = $this->getMockBuilder('\bblue\ruby\Component\Container\Container');
    }

    public function testRegisterClassWithoutAlias()
    {
        $class = new stdClass();
        $this->container->register($class);
        $this->assertTrue($this->container->has('stdClass'));
    }
}
