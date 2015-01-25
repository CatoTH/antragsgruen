<?php

namespace tests\codeception\unit\models;

use app\models\Motion;
use app\models\Site;
use Yii;
use yii\codeception\TestCase;
use app\models\LoginForm;
use Codeception\Specify;

class DBTest extends TestCase
{
    use Specify;

    /** @var \yii\db\Connection */
    protected $db;
    /** @var  string */
    protected $db_delete;

    protected function _before()
    {
        $init            = file_get_contents(Yii::$app->params['sql_test_schema_create']);
        $this->db        = Yii::$app->db;
        $this->db_delete = file_get_contents(Yii::$app->params['sql_test_schema_delete']);
        $command         = $this->db->createCommand($init);
        $command->execute();

        $testdata = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '../fixtures/data/testdata.sql');
        $command         = $this->db->createCommand($testdata);
        $command->execute();
    }

    protected function _after()
    {
        $command = $this->db->createCommand($this->db_delete);
        $command->execute();
    }

    public function testFindSite()
    {
        $model = null;
        $this->specify('should find test site', function () use ($model) {
            /** @var Site $model */
            $model = Site::findOne(1);
            expect('Expecting ID 1: ', ($model && $model->id == 2))->true();
        });
    }
    /*

    public function testLoginNoUser()
    {
        $model = new LoginForm([
            'username' => 'not_existing_username',
            'password' => 'not_existing_password',
        ]);

        $this->specify('user should not be able to login, when there is no identity', function () use ($model) {
            expect('model should not login user', $model->login())->false();
            expect('user should not be logged in', Yii::$app->user->isGuest)->true();
        });
    }

    public function testLoginWrongPassword()
    {
        $model = new LoginForm([
            'username' => 'demo',
            'password' => 'wrong_password',
        ]);

        $this->specify('user should not be able to login with wrong password', function () use ($model) {
            expect('model should not login user', $model->login())->false();
            expect('error message should be set', $model->errors)->hasKey('password');
            expect('user should not be logged in', Yii::$app->user->isGuest)->true();
        });
    }

    public function testLoginCorrect()
    {
        $model = new LoginForm([
            'username' => 'demo',
            'password' => 'demo',
        ]);

        $this->specify('user should be able to login with correct credentials', function () use ($model) {
            expect('model should login user', $model->login())->true();
            expect('error message should not be set', $model->errors)->hasntKey('password');
            expect('user should be logged in', Yii::$app->user->isGuest)->false();
        });
    }
    */

}
