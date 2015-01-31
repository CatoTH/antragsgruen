<?php

// @codingStandardsIgnoreFile

/**
 * Class AntragsgruenFunctionalTester
 * @SuppressWarnings(PHPMD)
 */
class AntragsgruenFunctionalTester extends FunctionalTester
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
}
