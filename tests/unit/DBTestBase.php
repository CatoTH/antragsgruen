<?php

namespace unit;

use Codeception\Specify;

require_once(__DIR__ . '/../config/AntragsgruenSetupDB.php');

class DBTestBase extends TestBase
{
    use Specify;
    use \app\tests\AntragsgruenSetupDB;

    protected function setUp(): void
    {

        parent::setUp();
        $this->createDB();
        $file = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '_data/dbdata1.sql';
        $this->populateDB($file);

        \yii::$app->db->close();
    }

    protected function tearDown(): void
    {
        $this->deleteDB();
        parent::tearDown();
    }
}
