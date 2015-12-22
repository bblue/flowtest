<?php

namespace bblue\ruby\Component\Common;

interface iGenericHandler
{
    public function build();
    public function store();
    public function get();
    public function set();
}