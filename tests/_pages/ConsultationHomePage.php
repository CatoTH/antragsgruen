<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * Represents contact page
 * @property \Tests\Support\AcceptanceTester $actor
 */
class ConsultationHomePage extends BasePage
{
    public string|array $route = 'consultation/index';

    /**
     * @param int    $motionTypeId
     * @param bool   $check
     * @param string $subdomain
     * @param string $path
     * @return MotionCreatePage
     */
    public function gotoMotionCreatePage(int $motionTypeId = 1, bool $check = true, string $subdomain = 'stdparteitag', string $path = 'std-parteitag'): MotionCreatePage
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
     * @param bool   $check
     * @return AmendmentCreatePage
     */
    public function gotoAmendmentCreatePage(string $motionSlug = '321-o-zapft-is', bool $check = true): AmendmentCreatePage
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
    public function gotoMotionView(int $motionId): MotionPage
    {
        $this->actor->click('.motionLink' . $motionId);
        return new MotionPage($this->actor);
    }

    /**
     * @param int $amendmentId
     * @return AmendmentPage
     */
    public function gotoAmendmentView(int $amendmentId): AmendmentPage
    {
        $this->actor->click('.amendment' . $amendmentId);
        return new AmendmentPage($this->actor);
    }
}
