<?php

namespace unit;

class TestBase extends \yii\codeception\TestCase
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->appConfig = 'tests/config/unit.php';
        parent::setUp();
    }
}
