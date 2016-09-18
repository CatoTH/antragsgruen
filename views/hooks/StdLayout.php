<?php

namespace app\views\hooks;

class StdLayout extends LayoutHooks
{
    use StdFunctionTrait;

    /**
     * @return string
     */
    public function beginPage()
    {
        return $this->getStdNavbarHeader();
    }
}