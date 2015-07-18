<?php

namespace app\tests\_pages;

use yii\codeception\BasePage;

/**
 * Represents contact page
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class ConsultationHomePage extends BasePage
{
    public $route = 'consultation/index';

    /**
     * @param int $motionTypeId
     * @param bool $check
     * @return MotionCreatePage
     */
    public function gotoMotionCreatePage($motionTypeId = 1, $check = true)
    {
        $page = MotionCreatePage::openBy(
            $this->actor,
            [
                'subdomain'        => 'stdparteitag',
                'consultationPath' => 'std-parteitag',
                'motionTypeId'     => $motionTypeId,
            ]
        );
        if ($check) {
            $this->actor->see(mb_strtoupper('Antrag stellen'), 'h1');
        }
        return $page;
    }

    /**
     * @param int $motionId
     * @param bool $check
     * @return MotionCreatePage
     */
    public function gotoAmendmentCreatePage($motionId = 2, $check = true)
    {
        $page = AmendmentCreatePage::openBy(
            $this->actor,
            [
                'subdomain'        => 'stdparteitag',
                'consultationPath' => 'std-parteitag',
                'motionId'         => $motionId,
            ]
        );
        if ($check) {
            $this->actor->see(mb_strtoupper('stellen'), 'h1');
        }
        return $page;
    }

    /**
     * @param int $motionId
     * @return MotionPage
     */
    public function gotoMotionView($motionId)
    {
        $this->actor->click('.motionLink' . $motionId);
        return new MotionPage($this->actor);
    }

    /**
     * @param int $amendmentId
     * @return AmendmentPage
     */
    public function gotoAmendmentView($amendmentId)
    {
        $this->actor->click('.amendment' . $amendmentId);
        return new AmendmentPage($this->actor);
    }
}
