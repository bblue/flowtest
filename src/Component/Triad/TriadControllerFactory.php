<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 02.02.2016
 * Time: 12:04
 */

namespace bblue\ruby\Component\Triad;


use bblue\ruby\Component\Router\Router;

class TriadControllerFactory implements iTriadControllerFactory
{
    /**
     * @var iTriadFactory
     */
    private $triadFactory;
    /**
     * @var iTriadDispatcher
     */
    private $triadDispatcher;
    /**
     * @var Router
     */
    private $router;

    /**
     * TriadControllerFactory constructor.
     * @param iTriadFactory    $triadFactory
     * @param iTriadDispatcher $triadDispatcher
     * @param Router           $router
     */
    public function __construct(iTriadFactory $triadFactory, iTriadDispatcher $triadDispatcher, Router $router)
    {
        $this->triadFactory = $triadFactory;
        $this->triadDispatcher = $triadDispatcher;
        $this->router = $router;
    }

    public function build(): iTriadController
    {
        return new TriadController($this->triadFactory, $this->triadDispatcher, $this->router);
    }
}