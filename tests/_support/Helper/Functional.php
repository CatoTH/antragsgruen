<?php

namespace Helper;

use Codeception\TestCase;

require_once(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' .
    DIRECTORY_SEPARATOR . 'config'
    . DIRECTORY_SEPARATOR . 'AntragsgruenSetupDB.php');

class Functional extends \Codeception\Module
{
    use \app\tests\AntragsgruenSetupDB;

    public function _before(TestCase $test)
    {
        $this->createDB();
    }

    public function _after(TestCase $test)
    {
        $this->deleteDB();
    }

    /**
     *
     */
    public function populateDBData1()
    {
        $this->populateDB(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR . '_data' . DIRECTORY_SEPARATOR . 'dbdata1.sql');
    }
}
