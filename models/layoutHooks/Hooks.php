<?php

namespace app\models\layoutHooks;

use app\models\db\ConsultationMotionType;
use app\models\db\Motion;

interface Hooks
{
    /**
     * @param $before
     * @return string
     */
    public function beforePage($before);

    /**
     * @param $before
     * @return string
     */
    public function beginPage($before);

    /**
     * @param $before
     * @return string
     */
    public function logoRow($before);

    /**
     * @param $before
     * @return string
     */
    public function beforeContent($before);

    /**
     * @param $before
     * @return string
     */
    public function beginContent($before);

    /**
     * @param $before
     * @return string
     */
    public function afterContent($before);

    /**
     * @param $before
     * @return string
     */
    public function endPage($before);

    /**
     * @param $before
     * @return string
     */
    public function renderSidebar($before);

    /**
     * @param $before
     * @return string
     */
    public function getSearchForm($before);

    /**
     * @param $before
     * @return string
     */
    public function getAntragsgruenAd($before);

    /**
     * @param $before
     * @param ConsultationMotionType $motionType
     * @return string
     */
    public function setSidebarCreateMotionButton($before, $motionType);

    /**
     * @param string $before
     * @return string
     */
    public function getStdNavbarHeader($before);

    /**
     * @param string $before
     * @return string
     */
    public function breadcrumbs($before);

    /**
     * @param string $before
     * @return string
     */
    public function footerLine($before);

    /**
     * @param string $before
     * @param Motion $motion
     * @return string
     */
    public function beforeMotionView($before, Motion $motion);

    /**
     * @param string $before
     * @param Motion $motion
     * @return string
     */
    public function afterMotionView($before, Motion $motion);

    /**
     * @param array $motionData
     * @param Motion $motion
     * @return array
     */
    public function getMotionViewData($motionData, Motion $motion);
}
