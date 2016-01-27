<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 10.01.2016
 * Time: 13:18
 */

namespace bblue\ruby\Component\Container;

class ProxyBuilder implements ContainerAwareInterface, iProxyBuilder
{
    use ContainerAwareTrait;

    public function buildFromReference(Reference $reference): Proxy
    {
    }
}