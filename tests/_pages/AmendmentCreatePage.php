<?php

namespace app\tests\_pages;

use yii\codeception\BasePage;

/**
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class AmendmentCreatePage extends BasePage
{
    public $route = 'amendment/create';

    /**
     * @param string $title
     */
    public function createAmendment($title)
    {
        $this->fillInValidSampleData($title);
        $this->saveForm();
        $this->actor->see(mb_strtoupper('Antrag bestätigen'), 'h1');
        $this->actor->submitForm('#amendmentConfirmForm', [], 'confirm');
        $this->actor->see(mb_strtoupper('Antrag eingereicht'), 'h1');
    }

    /**
     * @param string $title
     */
    public function fillInValidSampleData($title = 'Neuer Testantrag 1')
    {
        $this->actor->fillField('#sections_1', $title);
        $this->actor->fillField(['name' => 'Initiator[name]'], 'Mein Name');
        $this->actor->fillField(['name' => 'Initiator[contactEmail]'], 'test@example.org');
    }

    /**
     *
     */
    public function saveForm()
    {
        $this->actor->submitForm('#amendmentEditForm', [], 'save');
    }
}
