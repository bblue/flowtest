<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 19.01.2016
 * Time: 12:39
 */

namespace bblue\ruby\Component\Package;


interface iPackage
{
    public function bootPackage();

    public function isBooted();

    public function getName();
}