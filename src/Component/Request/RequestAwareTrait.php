<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 08.02.2016
 * Time: 17:25
 */

namespace bblue\ruby\Component\Request;

trait RequestAwareTrait
{
    /**
     * @var iRequest
     */
    protected $request;

    public function setRequest(iInternalRequest $request): self
    {
        $this->request = $request;
        return $this;
    }
}