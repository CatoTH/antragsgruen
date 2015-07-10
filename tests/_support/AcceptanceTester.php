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

    const FIRST_FREE_MOTION_ID           = 58;
    const FIRST_FREE_MOTION_TITLE_PREFIX = 'A4';
    const FIRST_FREE_MOTION_SECTION      = 26;
    const FIRST_FREE_AMENDMENT_ID        = 137;

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
     * @param int $motionId
     * @return MotionPage
     */
    public function gotoMotion($check = true, $motionId = 2)
    {
        $page = MotionPage::openBy(
            $this,
            [
                'subdomain'        => 'stdparteitag',
                'consultationPath' => 'std-parteitag',
                'motionId'         => $motionId,
            ]
        );
        if ($check) {
            $this->seeElement('.motionData');
        }
        return $page;
    }

    /**
     * @param bool $check
     * @param int $motionId
     * @param int $amendmentId
     * @return MotionPage
     */
    public function gotoAmendment($check = true, $motionId = 2, $amendmentId = 1)
    {
        $page = AmendmentPage::openBy(
            $this,
            [
                'subdomain'        => 'stdparteitag',
                'consultationPath' => 'std-parteitag',
                'motionId'         => $motionId,
                'amendmentId'      => $amendmentId
            ]
        );
        if ($check) {
            $this->seeElement('.motionData');
        }
        return $page;
    }

    /**
     * @return AdminIndexPage
     */
    public function loginAndGotoStdAdminPage()
    {
        $this->gotoConsultationHome();
        $this->loginAsStdAdmin();
        return $this->gotoStdAdminPage();
    }

    /**
     * @param bool $check
     * @return AdminIndexPage
     */
    public function gotoStdAdminPage($check = true)
    {
        $page = AdminIndexPage::openBy(
            $this,
            [
                'subdomain'        => 'stdparteitag',
                'consultationPath' => 'std-parteitag',
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
        $this->fillField('#password_input', 'testadmin');
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
        $this->fillField('#password_input', 'testuser');
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
