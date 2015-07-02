<?php

namespace unit;

use Codeception\Util\Autoload;

Autoload::addNamespace('unit', __DIR__);

class UserTest extends TestBase
{
    /**
     *
     */
    protected function setUp()
    {
        parent::setUp();
        // uncomment the following to load fixtures for user table
        //$this->loadFixtures(['user']);
    }

    // TODO add test methods here
}
