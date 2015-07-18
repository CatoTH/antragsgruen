<?php

namespace app\tests\_pages;

use yii\codeception\BasePage;

/**
 * Represents contact page
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class MotionCreatePage extends BasePage
{
    public $route = 'motion/create';

    /**
     * @param string $title
     */
    public function createMotion($title = 'Testantrag 1')
    {
        $this->fillInValidSampleData($title);
        $this->saveForm();
        $this->actor->see(mb_strtoupper('Antrag bestätigen'), 'h1');
        $this->actor->submitForm('#motionConfirmForm', [], 'confirm');
        $this->actor->see(mb_strtoupper('Antrag eingereicht'), 'h1');
    }

    /**
     * @param string $title
     */
    public function fillInValidSampleData($title = 'Testantrag 1')
    {
        $this->actor->fillField(['name' => 'sections[1]'], $title);
        $this->actor->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData("<p><strong>Test</strong></p>");');
        $this->actor->executeJS('CKEDITOR.instances.sections_3_wysiwyg.setData("<p><strong>Test 2</strong></p>");');
        $this->actor->fillField(['name' => 'Initiator[name]'], 'Mein Name');
        $this->actor->fillField(['name' => 'Initiator[contactEmail]'], 'test@example.org');
    }

    /**
     *
     */
    public function saveForm()
    {
        $this->actor->submitForm('#motionEditForm', [], 'save');
    }
}
