<?php

namespace app\views\hooks;

use app\models\settings\Layout;

abstract class LayoutHooks
{
    /** @var Layout */
    protected $layout;

    /**
     * LayoutHooks constructor.
     * @param Layout $layout
     */
    public function __construct(Layout $layout)
    {
        $this->layout = $layout;
    }

    /**
     * @return string
     */
    public function beforePage()
    {
        return '';
    }

    /**
     * @return string
     */
    public function beginPage()
    {
        return '';
    }

    /**
     * @return string
     */
    public function logoRow()
    {
        return '';
    }
}
