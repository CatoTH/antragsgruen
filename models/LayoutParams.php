<?php

namespace app\models;


class LayoutParams
{
    public $twocols             = false;
    public $menu                = array();
    public $breadcrumbs         = array();
    public $multimenu           = array();
    public $presidebar_html     = "";
    public $menus_html          = array();
    public $breadcrumbs_topname = null;
    public $robots_noindex      = false;
    public $extra_css           = array();

    /**
     * @param string $file
     */
    public function addCSS($file)
    {
        if (!in_array($file, $this->extra_css)) {
            $this->extra_css[] = $file;
        }
    }
}
