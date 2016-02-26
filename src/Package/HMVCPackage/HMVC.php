<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 22.02.2016
 * Time: 11:23
 */

namespace bblue\ruby\Package\HMVCPackage;


use bblue\ruby\Component\Package\AbstractPackage;
use bblue\ruby\Component\Request\InternalRequestHandler;

final class HMVC extends AbstractPackage
{
    public function boot()
    {
        $this->container->get('requestHandler')->registerAdapter(new InternalRequestHandler($this->container));
        return true;
    }
}