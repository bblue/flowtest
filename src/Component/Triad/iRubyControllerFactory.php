<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 01.02.2016
 * Time: 17:22
 */

namespace bblue\ruby\Component\Triad;

interface iRubyControllerFactory
{
    /**
     * Returns a controller
     * @param string $fqcn
     * @return iRubyController
     */
    public function build(string $fqcn): iRubyController;
}