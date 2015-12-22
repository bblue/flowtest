<?php
namespace bblue\ruby\Component\Core;

interface iUserProviderStack extends iUserProvider
{
    public function add(iUserProvider $provider);
}