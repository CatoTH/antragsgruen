<?php

use app\tests\_pages\AdminIndexPage;
use app\tests\_pages\AdminMotionListPage;
use app\tests\_pages\AmendmentPage;
use app\tests\_pages\ConsultationHomePage;
use app\tests\_pages\MotionCreatePage;
use app\tests\_pages\MotionPage;
use Helper\BasePage;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    public const FIRST_FREE_MOTION_ID              = 121;
    public const FIRST_FREE_MOTION_TITLE_PREFIX    = 'A9';
    public const FIRST_FREE_AMENDMENT_TITLE_PREFIX = 'Ä8';
    public const FIRST_FREE_MOTION_SECTION         = 51;
    public const FIRST_FREE_AMENDMENT_ID           = 284;
    public const FIRST_FREE_AGENDA_ITEM_ID         = 15;
    public const FIRST_FREE_COMMENT_ID             = 1;
    public const FIRST_FREE_MOTION_TYPE            = 17;
    public const FIRST_FREE_CONSULTATION_ID        = 11;
    public const FIRST_FREE_VOTING_BLOCK_ID        = 3;
    public const FIRST_FREE_CONTENT_ID             = 4;
    public const FIRST_FREE_USER_ID                = 9;
    public const FIRST_FREE_TAG_ID                 = 14;
    public const FIRST_FREE_USERGROUP_ID           = 39;

    public const ABSOLUTE_URL_TEMPLATE_SITE = 'http://antragsgruen-test.local/{SUBDOMAIN}/{PATH}';
    public const ABSOLUTE_URL_TEMPLATE = 'http://antragsgruen-test.local/{SUBDOMAIN}/{CONSULTATION}/{PATH}';

    public static $ACCEPTED_HTML_ERRORS = [
        'Bad value “popup” for attribute “rel”',
        'CKEDITOR',
        'autocomplete'
    ];

    /**
     * @param bool $check
     * @param string $subdomain
     * @param string $path
     * @return ConsultationHomePage
     */
    public function gotoConsultationHome($check = true, $subdomain = 'stdparteitag', $path = 'std-parteitag')
    {
        $page = ConsultationHomePage::openBy(
            $this,
            [
                'subdomain'        => $subdomain,
                'consultationPath' => $path,
            ]
        );
        if ($check && $subdomain == 'stdparteitag' && $path == 'std-parteitag') {
            $this->see('Test2', 'h1');
        }
        return $page;
    }

    /**
     * @param bool $check
     * @param string $motionSlug
     * @return MotionPage
     */
    public function gotoMotion($check = true, $motionSlug = '321-o-zapft-is')
    {
        if (is_numeric($motionSlug)) {
            /** @var \app\models\db\Motion $motion */
            $motion     = \app\models\db\Motion::findOne($motionSlug);
            $motionSlug = $motion->getMotionSlug();
        }
        $page = MotionPage::openBy(
            $this,
            [
                'subdomain'        => 'stdparteitag',
                'consultationPath' => 'std-parteitag',
                'motionSlug'       => $motionSlug,
            ]
        );
        if ($check) {
            $this->seeElement('.motionData');
        }
        $this->wait(0.1);
        return $page;
    }

    /**
     * @param bool $check
     * @param string $motionSlug
     * @param int $amendmentId
     * @return AmendmentPage
     */
    public function gotoAmendment($check = true, $motionSlug = '321-o-zapft-is', $amendmentId = 1)
    {
        $page = AmendmentPage::openBy(
            $this,
            [
                'subdomain'        => 'stdparteitag',
                'consultationPath' => 'std-parteitag',
                'motionSlug'       => $motionSlug,
                'amendmentId'      => $amendmentId
            ]
        );
        if ($check) {
            $this->seeElement('.motionData');
        }
        $this->wait(0.1);
        return $page;
    }

    /**
     * @param string $subdomain
     * @param string $path
     * @param int $motionTypeId
     * @return MotionCreatePage
     */
    public function gotoMotionCreatePage($subdomain = 'stdparteitag', $path = 'std-parteitag', $motionTypeId = 1)
    {
        $page = MotionCreatePage::openBy(
            $this,
            [
                'subdomain'        => $subdomain,
                'consultationPath' => $path,
                'motionTypeId'     => $motionTypeId,
            ]
        );
        $this->wait(0.1);
        return $page;
    }

    /**
     * @param BasePage|string $page
     * @param $params
     * @return BasePage
     */
    public function openPage($page, $params = [])
    {
        return $page::openBy($this, $params);
    }

    /**
     * @param string $subdomain
     * @param string $path
     * @return AdminIndexPage
     */
    public function loginAndGotoStdAdminPage($subdomain = 'stdparteitag', $path = 'std-parteitag')
    {
        $this->gotoConsultationHome(false, $subdomain, $path);
        $this->loginAsStdAdmin();
        return $this->gotoStdAdminPage($subdomain, $path);
    }

    public function loginAndGotoMotionList(string $subdomain = 'stdparteitag', string $path = 'std-parteitag'): AdminMotionListPage
    {
        $this->gotoConsultationHome(false, $subdomain, $path);
        $this->loginAsStdAdmin();
        return $this->gotoMotionList();
    }

    public function gotoStdAdminPage(string $subdomain = 'stdparteitag', string $path = 'std-parteitag'): AdminIndexPage
    {
        return AdminIndexPage::openBy(
            $this,
            [
                'subdomain'        => $subdomain,
                'consultationPath' => $path,
            ]
        );
    }

    public function gotoMotionList(): AdminMotionListPage
    {
        $this->click('#motionListLink');
        $this->see(mb_strtoupper('Liste: Anträge, Änderungsanträge'), 'h1');
        return new AdminMotionListPage($this);
    }

    protected function loginWithData(string $username, string $password): self
    {
        $this->see('LOGIN', '#loginLink');
        $this->click('#loginLink');

        $this->see('LOGIN', 'h1');
        $this->fillField('#username', $username);
        $this->fillField('#passwordInput', $password);
        $this->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');

        return $this;
    }

    public function loginAsStdAdmin(): self
    {
        return $this->loginWithData('testadmin@example.org', 'testadmin');
    }

    public function loginAsConsultationAdmin(): self
    {
        return $this->loginWithData('consultationadmin@example.org', 'consultationadmin');
    }

    public function loginAsProposalAdmin(): self
    {
        return $this->loginWithData('proposaladmin@example.org', 'proposaladmin');
    }

    public function loginAsGlobalAdmin(): self
    {
        return $this->loginWithData('globaladmin@example.org', 'testadmin');
    }

    public function loginAsStdUser(): self
    {
        return $this->loginWithData('testuser@example.org', 'testuser');
    }

    public function loginAsFixedDataUser(): self
    {
        return $this->loginWithData('fixeddata@example.org', 'testuser');
    }

    public function loginAsFixedDataAdmin(): self
    {
        return $this->loginWithData('fixedadmin@example.org', 'testadmin');
    }

    public function loginAsGruenesNetzUser(): void
    {
        $this->see('LOGIN', '#loginLink');
        $this->click('#loginLink');

        $this->see('LOGIN', 'h1');
        $this->fillField('#gruenesNetzAccount', 'DoeJane');
        $this->submitForm('#gruenesNetzLoginForm', [], 'gruenesNetzLogin');
        $this->seeElement('#logoutLink');
    }

    public function logout(): void
    {
        $this->see('LOGOUT', '#logoutLink');
        $this->click('#logoutLink');
    }

    public function clickJS(string $selector): void
    {
        $this->executeJS('document.querySelector("' . $selector . '").dispatchEvent(new MouseEvent("click", { view: window, bubbles: true, cancelable: true}))');
    }
}
