<?php

namespace app\tests\_pages;

use yii\codeception\BasePage;

/**
 * @property \AntragsgruenAcceptenceTester|\FunctionalTester $actor
 */
class AdminMotionPage extends BasePage
{
    public $route = 'admin/motion/update';

    /**
     *
     */
    public function saveForm()
    {
        $this->actor->submitForm('#motionUpdateForm', [], 'save');
    }
}
