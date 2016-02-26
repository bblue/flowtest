<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 01.02.2016
 * Time: 17:21
 */

namespace bblue\ruby\Component\Triad;

use bblue\ruby\Component\Container\Container;

/**
 * Class ControllerFactory
 * The controller factory will instantiate controller objects registered in the DI container
 * @package bblue\ruby\Component\Triad
 */
final class ControllerFactory implements iRubyControllerFactory
{
    /**
     * @var Container
     */
    private $container;

    /**
     * ControllerFactory constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Builds a controller object
     *
     * Currently this will only work with a fqcn
     *
     * @param string $fqcn      The controller object to build
     * @return iRubyController
     */
    public function build(string $fqcn): iRubyController
    {
        return $this->buildFromFqcn($fqcn);
    }

    /**
     * Get a controller object by its fqcn
     * @param string $fqcn      The requested controller object's fqcn
     * @return iRubyController  The controller object
     * @throws \Exception       When the requested object could not be built
     */
    public function buildFromFqcn(string $fqcn): iRubyController
    {
        if(!$this->container->has($fqcn)) {
            return $this->container->get($fqcn);
        }
        throw new \Exception('Unable to build '. $fqcn);
    }
}