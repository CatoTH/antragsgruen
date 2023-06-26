<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * @property \Tests\Support\AcceptanceTester $actor
 */
class AdminIndexPage extends BasePage
{
    public string|array $route = 'admin/index';

    /**
     * @param int $motionTypeId
     * @return \Tests\_pages\AdminMotionTypePage
     */
    public function gotoMotionTypes(int $motionTypeId): AdminMotionTypePage
    {
        $this->actor->click('.motionType' . $motionTypeId);
        $this->actor->see(mb_strtoupper('Antragstyp bearbeiten'), 'h1');
        return new AdminMotionTypePage($this->actor);
    }

    public function gotoConsultation(): AdminConsultationPage
    {
        $this->actor->click('#consultationLink');
        return new AdminConsultationPage($this->actor);
    }

    public function gotoAppearance(): AdminAppearancePage
    {
        $this->actor->click('#appearanceLink');
        return new AdminAppearancePage($this->actor);
    }

    public function gotoUserAdministration(): void
    {
        $this->actor->click('.siteUsers');
        $this->actor->wait(1);
    }

    public function gotoConsultationCreatePage(): void
    {
        $this->actor->click('.siteConsultationsLink');
    }

    public function gotoVotingPage(): VotingAdminPage
    {
        $this->actor->click('.votingAdminLink');
        $this->actor->wait(0.3);
        return new VotingAdminPage($this->actor);
    }
}
