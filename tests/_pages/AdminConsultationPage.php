<?php

namespace app\tests\_pages;

use yii\codeception\BasePage;

/**
 * Represents contact page
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class AdminConsultationPage extends BasePage
{
    public $route = 'admin/index/consultation';

    public static $maintainanceCheckbox = '#maintainanceMode';

    /**
     *
     */
    public function saveForm()
    {
        $this->actor->submitForm('#consultationSettingsForm', [], 'save');
    }
}
