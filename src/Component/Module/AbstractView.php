<?php

namespace bblue\ruby\Component\Module;

use bblue\ruby\Component\Container\ContainerAwareInterface;
use bblue\ruby\Component\Container\ContainerAwareTrait;
use bblue\ruby\Component\Request\RequestAwareTrait;
use bblue\ruby\Component\Request\RequestHandlerAwareTrait;
use bblue\ruby\Component\Triad\iRubyView;

abstract class AbstractView implements ContainerAwareInterface, iRubyView
{
    use ContainerAwareTrait;
    use RequestHandlerAwareTrait;
    use RequestAwareTrait;
}