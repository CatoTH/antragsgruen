<?php

namespace tests\codeception\unit\models;

use app\models\db\Site;
use Yii;
use yii\codeception\TestCase;
use Codeception\Specify;

require_once(__DIR__ . '/../../config/AntragsgruenSetupDB.php');

class DBTestBase extends TestCase
{
    use Specify;
    use \app\tests\AntragsgruenSetupDB;

    /**
     *
     */
    protected function setUp()
    {

        parent::setUp();
        $this->createDB();
        $file = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..';
        $file .= DIRECTORY_SEPARATOR . 'fixtures/dbdata1.sql';
        $this->populateDB($file);

        \yii::$app->db->close();
    }

    /**
     *
     */
    protected function tearDown()
    {
        $this->deleteDB();
        parent::tearDown();
    }
}
