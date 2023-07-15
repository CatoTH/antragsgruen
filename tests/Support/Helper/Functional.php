<?php

namespace Tests\Support\Helper;

use Codeception\Module;
use Codeception\TestInterface;
use Tests\config\AntragsgruenSetupDB;

class Functional extends Module
{
    use AntragsgruenSetupDB;

    public function _before(TestInterface $test)
    {
        $this->createDB();
    }

    public function _after(TestInterface $test)
    {
        $this->deleteDB();
    }

    public function populateDBData1(): void
    {
        $this->populateDB(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.
                          '..'.DIRECTORY_SEPARATOR.'Data'.DIRECTORY_SEPARATOR.'dbdata1.sql');
    }
}
