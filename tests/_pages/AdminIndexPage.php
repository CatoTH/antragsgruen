<?php

// @TODO Delete this page?

namespace app\tests\_pages;

use Helper\BasePage;

/**
 * Represents contact page
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class AdminIndexPage extends BasePage
{
    public $route = 'admin/index';

    /**
     * @param int $motionTypeId
     * @return AdminMotionTypePage
     */
    public function gotoMotionTypes($motionTypeId)
    {
        $this->actor->click('.motionType' . $motionTypeId);
        $this->actor->see(mb_strtoupper('Antragstyp bearbeiten'), 'h1');
        return new AdminMotionTypePage($this->actor);
    }

    /**
     * @return AdminConsultationPage
     */
    public function gotoConsultation()
    {
        $this->actor->click('#consultationLink');
        return new AdminConsultationPage($this->actor);
    }

    /**
     * @return AdminSiteAccessPage
     */
    public function gotoSiteAccessPage()
    {
        $this->actor->click('.siteAccessLink');
        return new AdminSiteAccessPage($this->actor);
    }

    /**
     */
    public function gotoConsultationCreatePage()
    {
        $this->actor->click('.siteConsultationsLink');
    }
}
