<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);

$I->populateDBData1();

$I->wantTo('enable collecting supporters, min. 1 female');
$page = $I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->dontSeeElement('#amendmentSupportersForm');
$I->executeJS('$("#sameInitiatorSettingsForAmendments input").prop("checked", false).trigger("change")');
$I->seeElement('#amendmentSupportersForm');
$I->dontSeeElement('#typeMinSupportersFemaleRowAmendment');
$I->checkOption("//input[@name='amendmentInitiatorSettings[contactGender]'][@value='2']"); // Required
$I->dontSeeElement('#typeMinSupportersFemaleRowAmendment');
$I->selectFueluxOption('#typeSupportTypeAmendment', 2); // Collection phase
$I->selectFueluxOption('#typePolicySupportAmendments', 2); // Logged in users
$I->checkOption("//input[@name='type[amendmentLikesDislikes][]'][@value='4']"); // Official
$I->seeElement('#typeMinSupportersFemaleRowAmendment');
$I->fillField('#typeMinSupportersAmendment', 1);
$I->fillField('#typeMinSupportersFemaleAmendment', 1);
$I->checkFueluxCheckbox('#typeAllowMoreSupporters .checkbox-custom');
$page->saveForm();


$I->wantTo('create an amendment');
$I->gotoConsultationHome()->gotoAmendmentCreatePage()->fillInValidSampleData();
$I->selectFueluxOption('#initiatorGender', 'male');
$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');
$url = $I->executeJS('return $("#urlSharing").val()');

$I->logout();
$I->loginAsStdUser();
$I->amOnPage($url);

$I->wantTo('support it as a second man');
$I->see('1 Unterstützer*innen, davon 1 Frau');
$I->see('aktueller Stand: 0 / 0');
$I->seeElement('.motionSupportForm');
$I->fillField("//input[@name='motionSupportOrga']", "TestOrga");
$I->selectFueluxOption('#motionSupportGender', 'male');
$I->submitForm('.motionSupportForm', [], 'motionSupport');
$I->see('Du unterstützt diesen Änderungsantrag nun.');
$I->see('aktueller Stand: 1 / 0');

$I->wantTo('support it as woman');
$I->submitForm('.motionSupportForm', [], 'motionSupportRevoke');
$I->see('aktueller Stand: 0 / 0');
$I->fillField("//input[@name='motionSupportOrga']", "TestOrga");
$I->selectFueluxOption('#motionSupportGender', 'female');
$I->submitForm('.motionSupportForm', [], 'motionSupport');
$I->see('Du unterstützt diesen Änderungsantrag nun.');
$I->see('Die Mindestzahl an Unterstützer*innen (1) wurde erreicht');
