<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * @property \Tests\Support\AcceptanceTester $actor
 */
class AdminMotionListPage extends BasePage
{
    public string|array $route = 'admin/motion-list/index';

    /**
     * @param int $motionId
     * @return AdminMotionPage
     */
    public function gotoMotionEdit(int $motionId): AdminMotionPage
    {
        $this->actor->click('.adminMotionTable .motion' . $motionId . ' .titleCol a');
        return new AdminMotionPage($this->actor);
    }

    /**
     * @param int $amendmentId
     * @return AdminAmendmentPage
     */
    public function gotoAmendmentEdit(int $amendmentId): AdminAmendmentPage
    {
        $this->actor->click('.adminMotionTable .amendment' . $amendmentId . ' .titleCol a');
        return new AdminAmendmentPage($this->actor);
    }
}
