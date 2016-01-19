<?php

namespace bblue\ruby\Component\Container;

interface ContainerAwareInterface
{
    public function setContainer(Container $container);

    public function hasContainer();
}