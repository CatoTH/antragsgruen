<?php

namespace app\models\layoutHooks;

use app\models\db\Consultation;
use app\models\db\ConsultationMotionType;
use app\models\db\Motion;

class HooksAdapter implements Hooks
{
    /** @var \app\models\settings\Layout */
    protected $layout;

    /** @var Consultation|null */
    protected $consultation;

    /**
     * HooksAdapter constructor.
     * @param \app\models\settings\Layout $layout
     * @param Consultation|null $consultation
     */
    public function __construct(\app\models\settings\Layout $layout, $consultation)
    {
        $this->layout = $layout;
        $this->consultation = $consultation;
    }

    /**
     * @param $before
     * @return string
     */
    public function beforePage($before)
    {
        return $before;
    }

    /**
     * @param $before
     * @return string
     */
    public function beginPage($before)
    {
        return $before;
    }

    /**
     * @param $before
     * @return string
     */
    public function logoRow($before)
    {
        return $before;
    }

    /**
     * @param $before
     * @return string
     */
    public function beforeContent($before)
    {
        return $before;
    }

    /**
     * @param $before
     * @return string
     */
    public function beginContent($before)
    {
        return $before;
    }

    /**
     * @param $before
     * @return string
     */
    public function afterContent($before)
    {
        return $before;
    }

    /**
     * @param $before
     * @return string
     */
    public function endPage($before)
    {
        return $before;
    }

    /**
     * @param $before
     * @return string
     */
    public function renderSidebar($before)
    {
        return $before;
    }

    /**
     * @param $before
     * @return string
     */
    public function getSearchForm($before)
    {
        return $before;
    }

    /**
     * @param $before
     * @return string
     */
    public function getAntragsgruenAd($before)
    {
        return $before;
    }

    /**
     * @param string $before
     * @param ConsultationMotionType $motionType
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setSidebarCreateMotionButton($before, $motionType)
    {
        return $before;
    }

    /**
     * @param string $before
     * @return string
     */
    public function getStdNavbarHeader($before)
    {
        return $before;
    }

    /**
     * @param string $before
     * @return string
     */
    public function breadcrumbs($before)
    {
        return $before;
    }

    /**
     * @param string $before
     * @return string
     */
    public function footerLine($before)
    {
        return $before;
    }

    /**
     * @param string $before
     * @param Motion $motion
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeMotionView($before, Motion $motion)
    {
        return $before;
    }

    /**
     * @param string $before
     * @param Motion $motion
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterMotionView($before, Motion $motion)
    {
        return $before;
    }
}
