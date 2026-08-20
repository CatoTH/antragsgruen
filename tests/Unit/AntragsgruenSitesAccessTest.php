<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\components\RequestContext;
use app\models\db\User;
use app\models\settings\AntragsgruenApp;
use app\plugins\antragsgruen_sites\controllers\ManagerController;
use Codeception\Attribute\Group;
use Tests\Support\Helper\DBTestBase;

#[Group('database')]
class AntragsgruenSitesAccessTest extends DBTestBase
{
    private function callCanSeeAllSites(ManagerController $controller): bool
    {
        $ref = new \ReflectionMethod($controller, 'canSeeAllSites');
        return $ref->invoke($controller);
    }

    public function testGuestCannotSeeAllSites(): void
    {
        RequestContext::setOverrideUser(null);
        $controller = new ManagerController('manager', \Yii::$app);
        $this->assertFalse($this->callCanSeeAllSites($controller));
    }

    public function testRegularUserCannotSeeAllSites(): void
    {
        $user = User::findOne(2);
        $this->assertNotNull($user);
        $this->assertFalse($user->isGruenesNetzUser());
        
        RequestContext::setOverrideUser($user);
        $controller = new ManagerController('manager', \Yii::$app);
        $this->assertFalse($this->callCanSeeAllSites($controller));
    }

    public function testGruenesNetzUserCanSeeAllSites(): void
    {
        $user = new User();
        $user->auth = 'https://some-name.netzbegruener.in/';
        $this->assertTrue($user->isGruenesNetzUser());

        RequestContext::setOverrideUser($user);
        $controller = new ManagerController('manager', \Yii::$app);
        $this->assertTrue($this->callCanSeeAllSites($controller));
    }

    public function testSuperuserCanSeeAllSites(): void
    {
        $user = User::findOne(3);
        $this->assertNotNull($user);
        AntragsgruenApp::getInstance()->adminUserIds = [$user->id];

        RequestContext::setOverrideUser($user);
        $controller = new ManagerController('manager', \Yii::$app);
        $this->assertTrue($this->callCanSeeAllSites($controller), 'Superusers must be allowed to see all sites');
    }
}
