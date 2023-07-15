<?php

/** @var \Codeception\Scenario $scenario */
use app\models\policies\All;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);

$I->populateDBData1();

$I->gotoConsultationHome()->gotoAmendmentView(1);
$I->dontSee('Unterstützer*innen');

$I->wantTo('enably supporting without login');
$I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->selectOption('#typePolicySupportAmendments', All::getPolicyID());
$I->checkOption('.amendmentSupportPolicy .amendmentSupport');
$I->submitForm('.adminTypeForm', [], 'save');

$I->gotoConsultationHome()->gotoAmendmentView(1);
$I->logout();
$I->see('Unterstützer*innen');
$I->dontSee('Du!', '.supporters');

$I->fillField('input[name=motionSupportName]', 'My name');
$I->fillField('input[name=motionSupportOrga]', 'Orga');
$I->submitForm('.motionSupportForm', [], 'motionSupport');

$I->see('Du!', '.supporters');

$I->submitForm('.motionSupportForm', [], 'motionSupportRevoke');
$I->see('Du stehst diesem Änderungsantrag wieder neutral gegenüber.');
$I->dontSee('Du!', '.supporters');
