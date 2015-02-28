<?php

// @codingStandardsIgnoreFile

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
}
