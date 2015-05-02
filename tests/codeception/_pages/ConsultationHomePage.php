<?php

namespace tests\codeception\_pages;

use yii\codeception\BasePage;

/**
 * Represents contact page
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class ConsultationHomePage extends BasePage
{
    public $route = 'consultation/index';

    /**
     * @param bool $check
     * @return MotionCreatePage
     */
    public function gotoMotionCreatePage($check = true)
    {
        $page = MotionCreatePage::openBy(
            $this->actor,
            [
                'subdomain'        => 'stdparteitag',
                'consultationPath' => 'std-parteitag',
            ]
        );
        if ($check) {
            $this->actor->see(mb_strtoupper('Antrag stellen'), 'h1');
        }
        return $page;
    }
}
