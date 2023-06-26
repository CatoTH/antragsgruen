<?php

/** @var \Codeception\Scenario $scenario */
use app\models\supportTypes\SupportBase;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);

$I->populateDBData1();

$I->wantTo('ensure its deactivated by default');
$page = $I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->selectOption('#typePolicySupportMotions', 2); // Logged in users
$I->selectOption('#typePolicySupportAmendments', 2); // Logged in users
$page->saveForm();

$I->gotoMotion();
$I->dontSeeElement('.motionSupportForm');

$I->gotoAmendment();
$I->dontSeeElement('.motionSupportForm');


$I->wantTo('activate officially supporting it');
$page = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->checkOption("//input[@name='type[motionLikesDislikes][]'][@value='4']"); // Official
$I->checkOption("//input[@name='type[amendmentLikesDislikes][]'][@value='4']"); // Official
$page->saveForm();

$I->gotoMotion();
$I->seeElement('.motionSupportForm');

$I->fillField('input[name=motionSupportName]', 'My name');
$I->fillField('input[name=motionSupportOrga]', 'Orga');
$I->submitForm('.motionSupportForm', [], 'motionSupport');

$I->see('My name (Orga)', '#supporters');
$I->submitForm('.motionSupportForm', [], 'motionSupportRevoke');
$I->dontSee('My name (Orga)', '#supporters');

$I->gotoAmendment();
$I->seeElement('.motionSupportForm');

$I->fillField('input[name=motionSupportName]', 'My name');
$I->fillField('input[name=motionSupportOrga]', 'Orga');
$I->submitForm('.motionSupportForm', [], 'motionSupport');

$I->see('My name (Orga)', '#supporters');
$I->submitForm('.motionSupportForm', [], 'motionSupportRevoke');
$I->dontSee('My name (Orga)', '#supporters');


$I->wantTo('ensure it is not enabled for published motions by default if there is a collection phase');

$page = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->selectOption('#typeSupportType', SupportBase::COLLECTING_SUPPORTERS);
$page->saveForm();

$I->gotoMotion();
$I->dontSeeElement('.motionSupportForm');

$I->gotoAmendment();
$I->dontSeeElement('.motionSupportForm');


$I->wantTo('enable it for collection phase');

$page = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->dontSeeElement('#typeAllowSupportingAfterPublication');
$I->checkOption('#typeAllowMoreSupporters');
$I->wait(0.3);
$I->seeElement('#typeAllowSupportingAfterPublication');
$I->checkOption('#typeAllowSupportingAfterPublication');
$page->saveForm();

$I->gotoMotion();
$I->seeElement('.motionSupportForm');

$I->fillField('input[name=motionSupportName]', 'My name');
$I->fillField('input[name=motionSupportOrga]', 'Orga');
$I->submitForm('.motionSupportForm', [], 'motionSupport');

$I->see('My name (Orga)', '#supporters');
$I->submitForm('.motionSupportForm', [], 'motionSupportRevoke');
$I->dontSee('My name (Orga)', '#supporters');

$I->gotoAmendment();
$I->seeElement('.motionSupportForm');

$I->fillField('input[name=motionSupportName]', 'My name');
$I->fillField('input[name=motionSupportOrga]', 'Orga');
$I->submitForm('.motionSupportForm', [], 'motionSupport');

$I->see('My name (Orga)', '#supporters');
$I->submitForm('.motionSupportForm', [], 'motionSupportRevoke');
$I->dontSee('My name (Orga)', '#supporters');
