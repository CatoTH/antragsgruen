<?php

namespace tests\codeception\_pages;

use yii\codeception\BasePage;

/**
 * Represents contact page
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class MotionCreatePage extends BasePage
{
    public $route = 'motion/create';

    /**
     */
    public function fillInValidSampleData($title = 'Testantrag 1')
    {
        $this->actor->fillField(['name' => 'sections[1]'], $title);
        if (method_exists($this->actor, 'executeJS')) {
            $this->actor->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData("<p><strong>Test</strong></p>");');
            $this->actor->executeJS('CKEDITOR.instances.sections_3_wysiwyg.setData("<p><strong>Test 2</strong></p>");');
        } else {
            $this->actor->fillField(['name' => 'sections[2]'], 'Testantrag Text\n2');
            $this->actor->fillField(['name' => 'sections[3]'], 'Testantrag Text\nBegründung');
        }
        $this->actor->fillField(['name' => 'Initiator[name]'], 'Mein Name');
        $this->actor->fillField(['name' => 'Initiator[contactEmail]'], 'test@example.org');
        $this->actor->selectOption('#motionType2', 2);
    }

    /**
     *
     */
    public function saveForm()
    {
        $this->actor->submitForm('#motionEditForm', [], 'save');
    }
}
