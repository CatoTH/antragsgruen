<?php

namespace app\models;


class LayoutParams
{
    public $twocols            = false;
    public $menu               = array();
    public $breadcrumbs        = null;
    public $multimenu          = array();
    public $preSidebarHtml     = '';
    public $menusHtml          = array();
    public $breadcrumbsTopname = 'Start';
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
