<?php

/** @var \Codeception\Scenario $scenario */
use app\models\db\ISupporter;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$createPage = $I->gotoConsultationHome()->gotoMotionCreatePage();
$createPage->fillInValidSampleData('Sample motion from an organization');
$I->selectOption('#personTypeOrga', (string)ISupporter::PERSON_ORGANIZATION);
$I->dontSeeElement('.supporterDataHead');
$I->dontSeeElement('.supporterData');
$I->dontSeeElement('#initiatorOrga');
$I->seeElement('#resolutionDate');

$I->dontSeeElement('.bootstrap-datetimepicker-widget');
$I->executeJS('$("#resolutionDateHolder").find(".input-group-addon").click()');
$I->seeElement('.bootstrap-datetimepicker-widget');
$I->executeJS('$("#resolutionDateHolder").find(".input-group-addon").click()');
$I->dontSeeElement('.bootstrap-datetimepicker-widget');

$I->fillField('#initiatorPrimaryName', 'My party');
$I->fillField('#initiatorContactName', 'Myself');
$I->fillField('#resolutionDate', '09.09.1999');

$createPage->saveForm();

$I->see('My party');
$I->see('Beschlossen am: 09.09.1999');

$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->submitForm('#motionConfirmedForm', [], '');



$I->wantTo('see if the data is visible');

$I->see('My party');
$I->see('Beschlossen am: 09.09.1999');

$I->click('.motionLink' . AcceptanceTester::FIRST_FREE_MOTION_ID);

$I->see('My party');
$I->see('Beschlossen am: 09.09.1999');
