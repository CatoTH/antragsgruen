<?php

/** @var \Codeception\Scenario $scenario */
use app\models\policies\All;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);

$I->populateDBData1();

$I->gotoConsultationHome()->gotoMotionView(2);
$I->dontSee('Unterstützer*innen');

$I->wantTo('enably supporting without login');
$I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->selectOption('#typePolicySupportMotions', All::getPolicyID());
$I->checkOption('.motionSupportPolicy .motionSupport');
$I->submitForm('.adminTypeForm', [], 'save');

$I->gotoConsultationHome()->gotoMotionView(2);
$I->logout();
$I->see('Unterstützer*innen');
$I->dontSee('Du!', '.supporters');

$I->fillField('input[name=motionSupportName]', 'My name');
$I->fillField('input[name=motionSupportOrga]', 'Orga');
$I->submitForm('.motionSupportForm', [], 'motionSupport');

$I->see('Du!', '.supporters');

$I->submitForm('.motionSupportForm', [], 'motionSupportRevoke');
$I->see('Du stehst diesem Antrag wieder neutral gegenüber.');
$I->dontSee('Du!', '.supporters');
