<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);

$I->populateDBData1();

$I->wantTo('enable collecting supporters, min. 1 female');
$page = $I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->dontSeeElement('#typeMinSupportersFemaleRow');
$I->checkOption("//input[@name='motionInitiatorSettings[contactGender]'][@value='2']"); // Required
$I->dontSeeElement('#typeMinSupportersFemaleRow');
$I->selectOption('#typeSupportType', 2); // Collection phase
$I->selectOption('#typePolicySupportMotions', 2); // Logged in users
$I->checkOption("//input[@name='type[motionLikesDislikes][]'][@value='4']"); // Official
$I->seeElement('#typeMinSupportersFemaleRow');
$I->fillField('#typeMinSupporters', 1);
$I->fillField('#typeMinSupportersFemale', 1);
$I->checkOption('#typeAllowMoreSupporters');
$page->saveForm();


$I->wantTo('create a motion');
$I->gotoConsultationHome()->gotoMotionCreatePage()->fillInValidSampleData();
$I->selectOption('#initiatorGender', 'Männlich');
$I->submitForm('#motionEditForm', [], 'save');
$I->submitForm('#motionConfirmForm', [], 'confirm');
$url = $I->executeJS('return $("#urlSharing").val()');

$I->logout();
$I->loginAsStdUser();
$I->amOnPage($url);

$I->wantTo('support it as a second man');
$I->see('1 Unterstützer*innen, davon 1 Frau');
$I->see('aktueller Stand: 0 / 0');
$I->seeElement('.motionSupportForm');
$I->fillField("//input[@name='motionSupportOrga']", "TestOrga");
$I->selectOption('#motionSupportGender', 'Männlich');
$I->submitForm('.motionSupportForm', [], 'motionSupport');
$I->see('Du unterstützt diesen Antrag nun.');
$I->see('aktueller Stand: 1 / 0');

$I->wantTo('support it as woman');
$I->submitForm('.motionSupportForm', [], 'motionSupportRevoke');
$I->see('aktueller Stand: 0 / 0');
$I->fillField("//input[@name='motionSupportOrga']", "TestOrga");
$I->selectOption('#motionSupportGender', 'Weiblich');
$I->submitForm('.motionSupportForm', [], 'motionSupport');
$I->see('Du unterstützt diesen Antrag nun.');
$I->see('Die Mindestzahl an Unterstützer*innen (1) wurde erreicht');
