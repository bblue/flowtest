<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 11.02.2016
 * Time: 20:36
 */

namespace bblue\ruby\Component\Core;


trait tExceptionAwareRequest
{
    /** @var  \Exception */
    private $e;
    public function hasException(): boolean
    {
        return isset($this->e);
    }
    public function getException(): \Exception
    {
        return $this->e;
    }
    public function setException(\Exception $e)
    {
        $this->e = $e;
    }
}