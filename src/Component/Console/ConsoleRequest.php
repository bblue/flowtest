<?php

namespace bblue\ruby\Component\Console;

use bblue\ruby\Component\Core\AbstractRequest;

final class ConsoleRequest extends AbstractRequest
{
    public function getClientAddress()
    {
        return 'cli';
    }
}