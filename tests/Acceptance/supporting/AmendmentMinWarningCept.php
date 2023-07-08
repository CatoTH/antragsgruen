<?php

/** @var \Codeception\Scenario $scenario */
use app\models\db\ISupporter;
use app\models\supportTypes\SupportBase;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('make sure the supporter-warning appears for natural persons');

$page = $I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);

$I->dontSeeElement('section.amendmentSupporters');
$I->seeCheckboxIsChecked('#sameInitiatorSettingsForAmendments input');
$I->executeJS('$("#sameInitiatorSettingsForAmendments input").prop("checked", false).trigger("change");');
$I->seeElement('section.amendmentSupporters');

$I->selectOption('#typeSupportTypeAmendment', SupportBase::GIVEN_BY_INITIATOR);
$I->fillField('#typeMinSupportersAmendment', 19);

$page->saveForm();

$I->gotoMotion();
$I->click('.sidebarActions .amendmentCreate a');

$I->seeElement('.supporterData');

$I->fillField('#initiatorPrimaryName', 'Mein Name');
$I->fillField('#initiatorEmail', 'test@example.org');
$I->executeJS('$("[required]").removeAttr("required");');
$I->submitForm('#amendmentEditForm', [], 'save');

$I->seeBootboxDialog('Es müssen mindestens 19 Unterstützer*innen angegeben werden');
$I->acceptBootboxAlert();


$I->wantTo('make sure it does not appear for organizations');

$I->selectOption('#personTypeOrga', (string)ISupporter::PERSON_ORGANIZATION);
$I->fillField('#initiatorPrimaryName', 'Mein Name');
$I->submitForm('#amendmentEditForm', [], 'save');

$I->seeBootboxDialog('Es muss ein Beschlussdatum angegeben werden');
$I->acceptBootboxAlert();


$I->fillField('#resolutionDate', '01.01.2000');
$I->submitForm('#amendmentEditForm', [], 'save');

$I->dontSeeBootboxDialog('Es müssen mindestens 19 Unterstützer*innen angegeben werden');
$I->dontSee('Not enough supporters.');
$I->see(mb_strtoupper('Änderungsantrag bestätigen'), 'h1');


$I->wantTo('make sure the changes are not active for motions');

$I->gotoConsultationHome();
$I->click('#sidebar .createMotion1');

$I->dontSeeElement('.supporterData');
