<?php

namespace app\views\hooks;

use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\db\ConsultationMotionType;
use app\models\settings\Layout;
use yii\helpers\Html;

abstract class LayoutHooks
{
    /** @var Layout */
    protected $layout;

    /** @var Consultation|null */
    protected $consultation;

    /**
     * LayoutHooks constructor.
     * @param Layout $layout
     * @param Consultation|null $consultation
     */
    public function __construct(Layout $layout, $consultation)
    {
        $this->layout = $layout;
        $this->consultation = $consultation;
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

    /**
     * @return string
     */
    public function beforeContent()
    {
        return '';
    }

    /**
     * @return string
     */
    public function afterContent()
    {
        return '';
    }

    /**
     * @return string
     */
    public function beginContent()
    {
        return '';
    }

    /**
     * @return string
     */
    public function endPage()
    {
        return '';
    }

    /**
     * @return string
     */
    public function renderSidebar()
    {
        $str = $this->layout->preSidebarHtml;
        if (count($this->layout->menusHtml) > 0) {
            $str .= '<div class="well hidden-xs">';
            $str .= implode('', $this->layout->menusHtml);
            $str .= '</div>';
        }
        $str .= $this->layout->postSidebarHtml;

        return $str;
    }

    /**
     * @return string
     */
    public function getSearchForm()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getAntragsgruenAd()
    {
        return '';
    }

    /**
     * @param ConsultationMotionType $motionType
     */
    public function setSidebarCreateMotionButton($motionType)
    {
        return;
    }
}
