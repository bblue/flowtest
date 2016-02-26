<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 08.02.2016
 * Time: 19:04
 */

namespace bblue\ruby\Component\Triad;


interface iRubyCommand
{
    public function getAsString(): string;
}