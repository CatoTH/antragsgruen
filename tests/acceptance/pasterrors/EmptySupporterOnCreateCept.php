<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$motionTypePage = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->selectFueluxOption('#typeSupportType', \app\models\supportTypes\SupportBase::GIVEN_BY_INITIATOR);
$I->fillField('#typeMinSupporters', 0);
$motionTypePage->saveForm();


$I->gotoConsultationHome();
$I->logout();
$I->loginAsStdUser();
$I->gotoMotion();
$I->click('.amendmentCreate a');

$I->seeInField('#initiatorPrimaryName', 'Testuser');
$I->dontSeeInField(['name' => 'supporters[name][]'], 'Testuser');



$I->gotoConsultationHome();
$I->click('.createMotion');

$I->seeInField('#initiatorPrimaryName', 'Testuser');
$I->dontSeeInField(['name' => 'supporters[name][]'], 'Testuser');
