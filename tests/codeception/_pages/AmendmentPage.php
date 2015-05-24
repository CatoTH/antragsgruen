<?php

namespace tests\codeception\_pages;

use yii\codeception\BasePage;

/**
 * Represents contact page
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class AmendmentPage extends BasePage
{
    public $route = 'amendment/view';

    /**
     * @param $subdomain
     * @param $consultationPath
     * @param $motionId
     * @param $amendmentId
     * @return AmendmentPage
     * @internal param bool $check
     */
    public function gotoAmendmentPage($subdomain, $consultationPath, $motionId, $amendmentId)
    {
        $page = AmendmentPage::openBy(
            $this->actor,
            [
                'subdomain'        => ($subdomain ? $subdomain : 'stdparteitag'),
                'consultationPath' => ($consultationPath ? $consultationPath : 'std-parteitag'),
                'motionId'         => $motionId,
                'amendmentId'      => $amendmentId,
            ]
        );
        return $page;
    }
}
