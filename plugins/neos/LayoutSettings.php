<?php

namespace app\plugins\neos;

use app\models\settings\Layout;

class LayoutSettings extends Layout
{
    /**
     * @param string $layout
     */
    public function setLayout($layout)
    {
        parent::setLayout($layout);
        \app\models\layoutHooks\Layout::addHook(new LayoutHooks($this, $this->consultation));
    }
}
