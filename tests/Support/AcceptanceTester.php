<?php
namespace Tests\Support;

use app\models\db\Motion;
use Codeception\Actor;
use Codeception\Lib\Friend;
use Tests\_pages\AdminIndexPage;
use Tests\_pages\AdminMotionListPage;
use Tests\_pages\AmendmentPage;
use Tests\_pages\ConsultationHomePage;
use Tests\_pages\ContentPage;
use Tests\_pages\MotionCreatePage;
use Tests\_pages\MotionPage;
use Tests\Support\Helper\BasePage;

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
 * @method Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends Actor
{
    // do not ever remove this line!
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
    public const FIRST_FREE_USER_ID                = 10;
    public const FIRST_FREE_TAG_ID                 = 14;
    public const FIRST_FREE_USERGROUP_ID           = 40;

    public const ABSOLUTE_URL_DOMAIN = 'test.antragsgruen.test';
    public const ABSOLUTE_URL_TEMPLATE_SITE = 'http://test.antragsgruen.test/{SUBDOMAIN}/{PATH}';
    public const ABSOLUTE_URL_TEMPLATE = 'http://test.antragsgruen.test/{SUBDOMAIN}/{CONSULTATION}/{PATH}';

    public const ACCEPTED_HTML_ERRORS = [
        'Bad value “popup” for attribute “rel”',
        'CKEDITOR',
        'autocomplete'
    ];

    public function gotoConsultationHome(bool $check = true, string $subdomain = 'stdparteitag', string $path = 'std-parteitag'): ConsultationHomePage
    {
        $page = ConsultationHomePage::openBy(
            $this,
            [
                'subdomain'        => $subdomain,
                'consultationPath' => $path,
            ]
        );
        if ($check && $subdomain==='stdparteitag' && $path==='std-parteitag') {
            $this->see('Test2', 'h1');
        }
        return $page;
    }

    public function gotoMotion(bool $check = true, string $motionSlug = '321-o-zapft-is'): MotionPage
    {
        if (is_numeric($motionSlug)) {
            /** @var Motion $motion */
            $motion     = Motion::findOne($motionSlug);
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
        $this->wait(0.3);
        return $page;
    }

    public function gotoAmendment(bool $check = true, string $motionSlug = '321-o-zapft-is', int $amendmentId = 1): AmendmentPage
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

    public function gotoMotionCreatePage(string $subdomain = 'stdparteitag', string $path = 'std-parteitag', int $motionTypeId = 1): MotionCreatePage
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

    public function gotoContentPage(string $pageSlug, string $subdomain = 'stdparteitag', string $path = 'std-parteitag'): ContentPage
    {
        $page = ContentPage::openBy(
            $this,
            [
                'subdomain'        => $subdomain,
                'consultationPath' => $path,
                'pageSlug'         => $pageSlug,
            ]
        );
        $this->wait(0.1);
        return $page;
    }

    public function openPage(BasePage|string $page, array $params = []): BasePage
    {
        return $page::openBy($this, $params);
    }

    public function loginAndGotoStdAdminPage(string $subdomain = 'stdparteitag', string $path = 'std-parteitag'): AdminIndexPage
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

    public function loginAsProgressAdmin(): self
    {
        return $this->loginWithData('progress@example.org', 'proposaladmin');
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

    public function loginAsDbwvTestUser(string $username): self
    {
        $this->see('LOGIN', 'h1');
        $this->fillField('#username', $username . '@example.org');
        $this->fillField('#passwordInput', 'Test');
        $this->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');

        return $this;
    }

    public function loginAsFixedDataAdmin(): self
    {
        return $this->loginWithData('fixedadmin@example.org', 'testadmin');
    }

    public function loginAsYfjUser(string $emailPrefix, int $userNo): self
    {
        $username = $emailPrefix . '-' . $userNo . '@example.org';
        return $this->loginWithData($username, 'Test');
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

    public function trigerChangeJS(string $selector): void
    {
        $this->executeJS('document.querySelector("' . $selector . '").dispatchEvent(new Event("change", { view: window, bubbles: true, cancelable: true}))');
    }
}
