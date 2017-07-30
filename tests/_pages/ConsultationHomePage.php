<?php

namespace app\tests\_pages;

use Helper\BasePage;

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
     * @param string $subdomain
     * @param string $path
     * @return MotionCreatePage
     */
    public function gotoMotionCreatePage($motionTypeId = 1, $check = true, $subdomain = 'stdparteitag', $path = 'std-parteitag')
    {
        $page = MotionCreatePage::openBy(
            $this->actor,
            [
                'subdomain'        => $subdomain,
                'consultationPath' => $path,
                'motionTypeId'     => $motionTypeId,
            ]
        );
        if ($check) {
            $this->actor->see(mb_strtoupper('Antrag stellen'), 'h1');
        }
        return $page;
    }

    /**
     * @param string $motionSlug
     * @param bool $check
     * @return AmendmentCreatePage
     */
    public function gotoAmendmentCreatePage($motionSlug = '321-o-zapft-is', $check = true)
    {
        $page = AmendmentCreatePage::openBy(
            $this->actor,
            [
                'subdomain'        => 'stdparteitag',
                'consultationPath' => 'std-parteitag',
                'motionSlug'       => $motionSlug,
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
