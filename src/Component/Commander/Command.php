<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 08.02.2016
 * Time: 20:29
 */

namespace bblue\ruby\Component\Commander;

use bblue\ruby\Component\Triad\iRubyCommand;

final class Command implements iRubyCommand
{
    /**
     * @var string
     */
    private $command;

    public function __construct(string $command)
    {
        $this->command = $command;
    }

    public function getAsString(): string
    {
        return $this->command;
    }
}