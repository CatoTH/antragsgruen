<?php
namespace Tests\Support\Helper;

use Tests\config\AntragsgruenSetupDB;
use yii;

class DBTestBase extends TestBase
{
    use AntragsgruenSetupDB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createDB();
        $file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'Data/dbdata1.sql';
        $this->populateDB($file);

        yii::$app->db->close();
    }

    protected function tearDown(): void
    {
        $this->deleteDB();
        parent::tearDown();
    }
}
