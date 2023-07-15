<?php

/** @var \Codeception\Scenario $scenario */
use app\models\db\ISupporter;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->gotoConsultationHome();
$I->loginAsStdAdmin();


$I->wantTo('set the resolution date as optional');
$page = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->seeCheckboxIsChecked("//input[@name='motionInitiatorSettings[hasResolutionDate]'][@value='2']"); // Required
$I->checkOption("//input[@name='motionInitiatorSettings[hasResolutionDate]'][@value='1']"); // Optional
$page->saveForm();
$I->seeCheckboxIsChecked("//input[@name='motionInitiatorSettings[hasResolutionDate]'][@value='1']");


$I->wantTo('see the field being optional');
$page = $I->gotoConsultationHome()->gotoMotionCreatePage();
$page->fillInValidSampleData();
$I->selectOption('#personTypeOrga', (string)ISupporter::PERSON_ORGANIZATION);
$I->seeElement('#resolutionDate');
$I->fillField('#resolutionDate', '');
$I->fillField('#initiatorPrimaryName', 'My party');

$I->click('#motionEditForm button[name=save]');
$I->seeElement('#motionConfirmForm');


$I->wantTo('deactivate the resolution date');
$page = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->seeCheckboxIsChecked("//input[@name='motionInitiatorSettings[hasResolutionDate]'][@value='1']"); // Optional
$I->checkOption("//input[@name='motionInitiatorSettings[hasResolutionDate]'][@value='0']"); // none
$page->saveForm();
$I->seeCheckboxIsChecked("//input[@name='motionInitiatorSettings[hasResolutionDate]'][@value='0']");


$I->wantTo('see the field being optional');
$page = $I->gotoConsultationHome()->gotoMotionCreatePage();
$page->fillInValidSampleData();
$I->selectOption('#personTypeOrga', (string)ISupporter::PERSON_ORGANIZATION);
$I->dontSeeElement('#resolutionDate');
$I->fillField('#initiatorPrimaryName', 'My party');

$I->click('#motionEditForm button[name=save]');
$I->seeElement('#motionConfirmForm');
