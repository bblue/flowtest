<?php
namespace App\Factories;

use App\Factory;
use App\ListenerProxy;

final class Listener extends Factory
{
    private $serviceFactory;

    public function __construct(Service $serviceFactory)
    {
        $this->serviceFactory= $serviceFactory;
    }

    protected function construct($sListener)
    {
    	return new ListenerProxy($this, $sListener);
    }

    public function createObject($sListener)
    {
    	$sListener = 'listeners\\' . $sListener;
    	return new $sListener($this->serviceFactory);
    }
}