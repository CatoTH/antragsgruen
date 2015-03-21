<?php

// @codingStandardsIgnoreFile
use tests\codeception\_pages\ConsultationHomePage;

/**
 * Class AntragsgruenAcceptenceTester
 * @SuppressWarnings(PHPMD)
 */
class AntragsgruenAcceptenceTester extends AcceptanceTester
{
    use \app\tests\AntragsgruenSetupDB;

    /**
     * @param \Codeception\Scenario $scenario
     */
    public function __construct(\Codeception\Scenario $scenario)
    {
        parent::__construct($scenario);
        $this->createDB();
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->deleteDB();
    }


    public function populateDBData1()
    {
        $this->populateDB(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'fixtures' .
            DIRECTORY_SEPARATOR . 'dbdata1.sql');
    }

    public function gotoStdConsultationHome()
    {
        ConsultationHomePage::openBy(
            $this,
            [
                'subdomain'        => 'stdparteitag',
                'consultationPath' => 'std-parteitag',
            ]
        );
        $this->see('Test2', 'h1');
    }

    public function loginAsStdAdmin()
    {
        $this->see('LOGIN', '#loginLink');
        $this->click('#loginLink');

        $this->see('LOGIN', 'h1');
        $this->fillField('#username', 'testadmin@example.org');
        $this->fillField('#password_input', 'testadmin');
        $this->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');

        $this->see('ADMIN', '#adminLink');
    }
}
