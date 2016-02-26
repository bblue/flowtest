<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 20.02.2016
 * Time: 21:12
 */

namespace bblue\ruby\Component\Request;

interface iHttpRequest extends iRequest
{
    public function setUrl(string $url);
    public function getUrl(): string;
}