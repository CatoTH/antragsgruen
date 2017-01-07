<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->dontSeeElement('#forceMotionRow');
$I->dontSeeCheckboxIsChecked('#singleMotionMode');

$I->wantTo('enable single-motion-mode');
$I->checkOption('#singleMotionMode');
$I->seeElement('#forceMotionRow');
$I->selectOption('#forceMotion', 3);
$I->submitForm('#consultationSettingsForm', [], 'save');

$I->seeElement('#forceMotionRow');
$I->seeCheckboxIsChecked('#singleMotionMode');


$I->wantTo('check that the setting is active');
$I->click('.homeLinkLogo');
$I->see('A3: Textformatierungen', 'h1');

$I->logout();
$I->click('.breadcrumb a');
$I->see('A3: Textformatierungen', 'h1');


$I->wantTo('unpublish the motion');
$I->loginAsStdAdmin();
$I->click('#sidebar .adminEdit a');
$I->executeJS('$("#motionStatus").selectlist("selectByValue", "' . \app\models\db\Motion::STATUS_DRAFT . '");');
$I->submitForm('#motionUpdateForm', [], 'save');


$I->click('.homeLinkLogo');
$I->dontSee('A3: Textformatierungen', 'h1');
$I->see('Dieser Antrag ist noch nicht sichtbar.');
$I->seeElement('#sidebar .adminEdit');

$I->logout();
$I->dontSee('A3: Textformatierungen', 'h1');
$I->see('Dieser Antrag ist noch nicht sichtbar.');
$I->dontSeeElement('#sidebar li');
