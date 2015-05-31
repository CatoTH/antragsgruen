<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->gotoStdConsultationHome();
$I->loginAsStdAdmin();
$motionTypePage = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->selectOption('#typeInitiatorForm', \app\models\initiatorForms\WithSupporters::getTitle());
$I->fillField('#typeMinSupporters', 0);
$motionTypePage->saveForm();
$I->seeOptionIsSelected('#typeInitiatorForm', \app\models\initiatorForms\WithSupporters::getTitle());


$I->gotoStdConsultationHome();
$I->gotoMotion();
$I->click('.amendmentCreate a');

$I->seeInField('#initiatorName', 'Testadmin');
$I->dontSeeInField(['name' => 'supporters[name][]'], 'Testadmin');



$I->gotoStdConsultationHome();
$I->click('.createMotion');

$I->seeInField('#initiatorName', 'Testadmin');
$I->dontSeeInField(['name' => 'supporters[name][]'], 'Testadmin');
