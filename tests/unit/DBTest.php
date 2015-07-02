<?php

namespace unit;

use app\models\db\Site;
use Yii;
use Codeception\Specify;
use Codeception\Util\Autoload;

Autoload::addNamespace('unit', __DIR__);

class DBTest extends DBTestBase
{
    /**
     *
     */
    public function testFindSite()
    {
        $model = null;
        // 'should find test site'

        $model = Site::findOne(1);
        if (!$model) {
            $this->fail('Not found');
        } else {
            $this->assertEquals($model->id, 1);
        }
    }
}
