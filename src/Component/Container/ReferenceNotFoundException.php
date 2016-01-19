<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 13.01.2016
 * Time: 21:19
 */

namespace bblue\ruby\Component\Container;


final class ReferenceNotFoundException extends \Exception
{
    private $var;

    public function __construct($message, $var, $code = null, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->var = $var;
    }

    public function getVar()
    {
        return $this->var;
    }
}