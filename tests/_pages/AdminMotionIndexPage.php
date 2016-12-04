<?php

namespace app\tests\_pages;

use Helper\BasePage;

/**
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class AdminMotionIndexPage extends BasePage
{
    public $route = 'admin/motion/index';

    /**
     * @param int $motionId
     * @return AdminMotionPage
     */
    public function gotoMotionPage($motionId)
    {
        $this->actor->click('.motionEditLink' . $motionId);
        return new AdminMotionPage($this->actor);
    }
}
