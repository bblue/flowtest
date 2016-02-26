<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 01.02.2016
 * Time: 17:19
 */

namespace bblue\ruby\Component\Router;

interface iRoute
{
    public function getCommand(): string;

    public function getControllerCN(): string;

    public function getModelCN(): string;

    public function getView(): string;

    public function getViewCN(): string;

    public function hasModelFqcn(): bool;
}