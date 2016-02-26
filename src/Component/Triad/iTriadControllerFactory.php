<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 02.02.2016
 * Time: 11:58
 */

namespace bblue\ruby\Component\Triad;


interface iTriadControllerFactory
{
    public function build(): iTriadController;
}