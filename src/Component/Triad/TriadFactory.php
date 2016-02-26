<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 01.02.2016
 * Time: 22:11
 */

namespace bblue\ruby\Component\Triad;

use bblue\ruby\Component\Container\Container;
use bblue\ruby\Component\Container\ContainerAwareInterface;
use bblue\ruby\Component\Container\ContainerAwareTrait;
use bblue\ruby\Component\Router\iRoute;

final class TriadFactory implements iTriadFactory, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var iTriadDispatcher
     */
    private $triadDispatcher;

    /**
     * TriadFactory constructor.
     * @param Container        $container
     * @param iTriadDispatcher $triadDispatcher
     */
    public function __construct(Container $container, iTriadDispatcher $triadDispatcher)
    {
        $this->container = $container;
        $this->triadDispatcher = $triadDispatcher;
    }

    public function build(iRoute $route): iTriad
    {
        // Build the MVC structure
        $model = $this->fetchModel($route);
        $controller = $this->fetchController($route);
        $view = $this->fetchView($route);
        // Construct the triad and return
        return new Triad($model, $view, $controller);
    }

    private function fetchController(iRoute $route): iRubyController
    {
        return $this->fetchFromContainer($route->getControllerCN());
    }

    private function fetchView(iRoute $route): iRubyView
    {
        return $this->fetchFromContainer($route->getViewCN());
    }

    private function fetchModel(iRoute $route): iRubyModel
    {
        return ($route->hasModelFqcn()) ? $this->fetchFromContainer($route->getModelCN()) : $this->container;
    }

    private function fetchFromContainer(string $var)
    {
        if($this->container->has($var)) {
            return $this->container->get($var);
        } else {
            throw new \Exception('The alias|fqcn provided by $route is not recognized ('.$var.')');
        }
    }
}