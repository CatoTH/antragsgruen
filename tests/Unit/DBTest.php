<?php

namespace Tests\Unit;

use app\models\db\Site;
use Codeception\Attribute\Group;
use Tests\Support\Helper\DBTestBase;

#[Group('database')]
class DBTest extends DBTestBase
{
    public function testFindSite(): void
    {
        $model = Site::findOne(1);
        if (!$model) {
            $this->fail('Not found');
        } else {
            $this->assertEquals(1, $model->id);
        }
    }
}
