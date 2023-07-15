<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * Represents contact page
 * @property \Tests\Support\AcceptanceTester $actor
 */
class AmendmentPage extends BasePage
{
    public string|array $route = 'amendment/view';

    /**
     * @param string $subdomain
     * @param string $consultationPath
     * @param string $motionSlug
     * @param int    $amendmentId
     * @return AmendmentPage
     * @internal param bool $check
     */
    public function gotoAmendmentPage(string $subdomain, string $consultationPath, string $motionSlug, int $amendmentId): AmendmentPage
    {
        $page = self::openBy(
            $this->actor,
            [
                'subdomain'        => ($subdomain ?: 'stdparteitag'),
                'consultationPath' => ($consultationPath ?: 'std-parteitag'),
                'motionSlug'       => $motionSlug,
                'amendmentId'      => $amendmentId,
            ]
        );
        return $page;
    }
}
