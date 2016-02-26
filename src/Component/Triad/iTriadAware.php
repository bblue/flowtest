<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 07.02.2016
 * Time: 07:53
 */

namespace bblue\ruby\Component\Triad;


interface iTriadAware
{
    public function setTriadFactory(iTriadFactory $triadFactory): self;
}