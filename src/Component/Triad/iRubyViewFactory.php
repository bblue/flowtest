<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 01.02.2016
 * Time: 22:20
 */

namespace bblue\ruby\Component\Triad;

interface iRubyViewFactory
{
    /**
     * Return a view object by its fqcn
     * @param string $fqcn
     * @return iRubyView
     */
    public function buildFromFqcn(string $fqcn): iRubyView;
}