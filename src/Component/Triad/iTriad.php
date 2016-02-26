<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 01.02.2016
 * Time: 17:12
 */

namespace bblue\ruby\Component\Triad;

use bblue\ruby\Component\Core\iRequest;
use bblue\ruby\Component\Core\iResponse;

interface iTriad
{
    public function getController(): iRubyController;
    public function getModel(): iRubyModel;
    public function getView(): iRubyView;
}