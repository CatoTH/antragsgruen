<?php

namespace app\tests\_pages;

use Helper\BasePage;

/**
 * Represents contact page
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class AmendmentPage extends BasePage
{
    public $route = 'amendment/view';

    /**
     * @param string $subdomain
     * @param string $consultationPath
     * @param string $motionSlug
     * @param int $amendmentId
     * @return AmendmentPage
     * @internal param bool $check
     */
    public function gotoAmendmentPage($subdomain, $consultationPath, $motionSlug, $amendmentId)
    {
        $page = AmendmentPage::openBy(
            $this->actor,
            [
                'subdomain'        => ($subdomain ? $subdomain : 'stdparteitag'),
                'consultationPath' => ($consultationPath ? $consultationPath : 'std-parteitag'),
                'motionSlug'       => $motionSlug,
                'amendmentId'      => $amendmentId,
            ]
        );
        return $page;
    }
}
