<?php

namespace app\models\settings;


use app\components\UrlHelper;
use app\models\db\Consultation;

class Layout
{
    public $menu            = array();
    public $breadcrumbs     = null;
    public $multimenu       = array();
    public $preSidebarHtml  = '';
    public $postSidebarHtml = '';
    public $menusHtml       = array();
    public $robotsNoindex   = false;
    public $extraCss        = array();
    public $extraJs         = array();
    public $onloadJs        = array();

    /** @var Consultation|null */
    private $consultation;


    /**
     * @param Consultation $consultation
     */
    public function setConsultation(Consultation $consultation)
    {
        $this->consultation = $consultation;
        if ($consultation && count($this->breadcrumbs) == 0) {
            $this->breadcrumbs[UrlHelper::createUrl('consultation/index')] = $consultation->titleShort;
        }
    }

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
