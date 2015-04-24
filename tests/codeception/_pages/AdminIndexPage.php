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
     * @return AdminMotionSectionPage
     */
    public function gotoMotionSections()
    {
        $this->actor->click('.motionSections');
        $this->actor->see(mb_strtoupper('Antrags-Abschnitte'), 'h1');
        return new AdminMotionSectionPage($this->actor);
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
