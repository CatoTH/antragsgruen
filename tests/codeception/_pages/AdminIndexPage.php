<?php

namespace tests\codeception\_pages;

use yii\codeception\BasePage;

/**
 * Represents contact page
 * @property \AntragsgruenAcceptenceTester|\FunctionalTester $actor
 */
class AdminIndexPage extends BasePage
{
    public $route = 'admin/index';

    /**
     * @param int $motionTypeId
     * @return AdminMotionSectionPage
     */
    public function gotoMotionSections($motionTypeId)
    {
        $this->actor->click('.motionSections' . $motionTypeId);
        $this->actor->see(mb_strtoupper('Antrags-Abschnitte'), 'h1');
        return new AdminMotionSectionPage($this->actor);
    }


    /**
     * @return AdminMotionIndexPage
     */
    public function gotoMotionIndex()
    {
        $this->actor->click('.motionIndex');
        $this->actor->see(mb_strtoupper('Anträge'), 'h1');
        return new AdminMotionIndexPage($this->actor);
    }

    /**
     * @return AdminConsultationPage
     */
    public function gotoConsultation()
    {
        $this->actor->click('#consultationLink');
        return new AdminConsultationPage($this->actor);
    }
}
