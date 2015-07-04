<?php

namespace app\tests\_pages;

use yii\codeception\BasePage;

/**
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class AdminMotionListPage extends BasePage
{
    public $route = 'admin/motion/listall';

    /**
     * @param int $motionId
     * @return AdminMotionPage
     */
    public function gotoMotion($motionId)
    {
        $this->actor->click('.adminMotionTable .motion' . $motionId . ' .prefixCol a');
        return new AdminMotionPage($this->actor);
    }

    /**
     * @param int $amendmentId
     * @return AdminAmendmentPage
     */
    public function gotoAmendment($amendmentId)
    {
        $this->actor->click('.adminMotionTable .amendment' . $amendmentId . ' .prefixCol a');
        return new AdminAmendmentPage($this->actor);
    }
}
