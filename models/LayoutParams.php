<?php

namespace app\models;


class LayoutParams
{
    public $twocols            = false;
    public $menu               = array();
    public $breadcrumbs        = array();
    public $multimenu          = array();
    public $presidebarHtml     = "";
    public $menusHtml          = array();
    public $breadcrumbsTopname = null;
    public $robotsNoindex      = false;
    public $extraCss           = array();
    public $extraJs            = array();
    public $onloadJs           = array();

    /**
     * @param string $file
     */
    public function addCSS($file)
    {
        if (!in_array($file, $this->extraCss)) {
            $this->extraCss[] = $file;
        }
    }

    /**
     * @param string $exec_js
     */
    public function addOnLoadJS($exec_js)
    {
        $this->onloadJs[] = $exec_js;
    }

    /**
     * @param string $file
     */
    public function addJS($file)
    {
        if (!in_array($file, $this->extraJs)) {
            $this->extraJs[] = $file;
        }
    }
}
