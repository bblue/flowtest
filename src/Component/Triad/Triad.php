<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 01.02.2016
 * Time: 21:10
 */

namespace bblue\ruby\Component\Triad;

final class Triad implements iTriad
{
    /**
     * @var iRubyController
     */
    private $controller;
    /**
     * @var iRubyView
     */
    private $view;
    /**
     * @var iRubyModel
     */
    private $model;

    /**
     * Triad constructor.
     * @param iRubyModel      $model
     * @param iRubyView       $view
     * @param iRubyController $controller
     */
    public function __construct(iRubyModel $model, iRubyView $view, iRubyController $controller)
    {
        $this->model = $model;
        $this->view = $view;
        $this->controller = $controller;
    }

    public function getController(): iRubyController
    {
        return $this->controller;
    }

    public function getModel(): iRubyModel
    {
        return $this->model;
    }

    public function getView(): iRubyView
    {
        return $this->view;
    }
}