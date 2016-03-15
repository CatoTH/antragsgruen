<?php
use app\tests\_pages\AdminIndexPage;
use app\tests\_pages\AmendmentPage;
use app\tests\_pages\ConsultationHomePage;
use app\tests\_pages\MotionPage;

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

    const FIRST_FREE_MOTION_ID              = 117;
    const FIRST_FREE_MOTION_TITLE_PREFIX    = 'A7';
    const FIRST_FREE_AMENDMENT_TITLE_PREFIX = 'Ä8';
    const FIRST_FREE_MOTION_SECTION         = 33;
    const FIRST_FREE_AMENDMENT_ID           = 278;
    const FIRST_FREE_AGENDA_ITEM_ID         = 15;
    const FIRST_FREE_COMMENT_ID             = 1;
    const FIRST_FREE_MOTION_TYPE            = 11;
    const FIRST_FREE_CONSULTATION_ID        = 8;

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
    public function gotoMotion($check = true, $motionSlug = '2')
    {
        if (is_numeric($motionSlug)) {
            /** @var \app\models\db\Motion $motion */
            $motion = \app\models\db\Motion::findOne($motionSlug);
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
        return $page;
    }

    /**
     * @param bool $check
     * @param string $motionSlug
     * @param int $amendmentId
     * @return MotionPage
     */
    public function gotoAmendment($check = true, $motionSlug = '2', $amendmentId = 1)
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
        return $page;
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
        return $this->gotoStdAdminPage(false, $subdomain, $path);
    }

    /**
     * @param bool $check
     * @param string $subdomain
     * @param string $path
     * @return AdminIndexPage
     */
    public function gotoStdAdminPage($check = true, $subdomain = 'stdparteitag', $path = 'std-parteitag')
    {
        $page = AdminIndexPage::openBy(
            $this,
            [
                'subdomain'        => $subdomain,
                'consultationPath' => $path,
            ]
        );
        return $page;
    }

    /**
     *
     */
    public function loginAsStdAdmin()
    {
        $this->see('LOGIN', '#loginLink');
        $this->click('#loginLink');

        $this->see('LOGIN', 'h1');
        $this->fillField('#username', 'testadmin@example.org');
        $this->fillField('#passwordInput', 'testadmin');
        $this->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');
    }

    /**
     *
     */
    public function loginAsGlobalAdmin()
    {
        $this->see('LOGIN', '#loginLink');
        $this->click('#loginLink');

        $this->see('LOGIN', 'h1');
        $this->fillField('#username', 'globaladmin@example.org');
        $this->fillField('#passwordInput', 'testadmin');
        $this->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');
    }

    /**
     *
     */
    public function loginAsStdUser()
    {
        $this->see('LOGIN', '#loginLink');
        $this->click('#loginLink');

        $this->see('LOGIN', 'h1');
        $this->fillField('#username', 'testuser@example.org');
        $this->fillField('#passwordInput', 'testuser');
        $this->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');
    }

    /**
     *
     */
    public function loginAsWurzelwerkUser()
    {
        $this->see('LOGIN', '#loginLink');
        $this->click('#loginLink');

        $this->see('LOGIN', 'h1');
        $this->fillField('#wurzelwerkAccount', 'DoeJane');
        $this->submitForm('#wurzelwerkLoginForm', [], 'wurzelwerkLogin');
        $this->seeElement('#logoutLink');
    }

    /**
     *
     */
    public function logout()
    {
        $this->see('LOGOUT', '#logoutLink');
        $this->click('#logoutLink');
    }
}
