<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 19.02.2016
 * Time: 21:04
 */

namespace bblue\ruby\Component\Core;


interface iAdapterAware
{
    public function registerAdapter(iAdapterImplementation $adapter, string $identifier = null);
}