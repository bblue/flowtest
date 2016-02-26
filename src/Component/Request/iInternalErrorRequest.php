<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 20.02.2016
 * Time: 12:49
 */

namespace bblue\ruby\Component\Request;


interface iInternalErrorRequest
{
    public function __construct(\Throwable $t, iRequest $previousRequest);

    /**
     * @return iRequest
     */
    public function getPreviousRequest(): iRequest;

    /**
     * @return \Throwable
     */
    public function getError(): \Throwable;
}