<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 01.02.2016
 * Time: 17:38
 */

namespace bblue\ruby\Component\Response;


interface iResponse
{
    public function send();
    public function setOutput($output);
}