<?php

namespace app\tests\_pages;

use Helper\BasePage;

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
    public function gotoMotionEdit($motionId)
    {
        $this->actor->click('.adminMotionTable .motion' . $motionId . ' .titleCol a');
        return new AdminMotionPage($this->actor);
    }

    /**
     * @param int $amendmentId
     * @return AdminAmendmentPage
     */
    public function gotoAmendmentEdit($amendmentId)
    {
        $this->actor->click('.adminMotionTable .amendment' . $amendmentId . ' .titleCol a');
        return new AdminAmendmentPage($this->actor);
    }
}
