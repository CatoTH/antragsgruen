<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('make sure the supporter-warning appears for natural persons');

$page = $I->gotoConsultationHome(true, 'bdk', 'bdk');
$I->loginAsStdAdmin();
$I->click('.motionLink4');
$I->click('.sidebarActions .amendmentCreate a');

$I->fillField('#initiatorPrimaryName', 'Mein Name');
$I->fillField('#initiatorEmail', 'test@example.org');
$I->executeJS('$("[required]").removeAttr("required");');
$I->submitForm('#amendmentEditForm', [], 'save');

$I->seeBootboxDialog('Es müssen mindestens 19 Unterstützer*innen angegeben werden');
$I->acceptBootboxAlert();


$I->wantTo('make sure it does not appear for organizations');

$I->selectOption('#personTypeOrga', \app\models\db\ISupporter::PERSON_ORGANIZATION);
$I->fillField('#initiatorPrimaryName', 'Mein Name');
$I->submitForm('#amendmentEditForm', [], 'save');

$I->seeBootboxDialog('Es muss ein Beschlussdatum angegeben werden');
$I->acceptBootboxAlert();


$I->fillField('#resolutionDate', '01.01.2000');
$I->submitForm('#amendmentEditForm', [], 'save');

$I->dontSeeBootboxDialog('Es müssen mindestens 19 Unterstützer*innen angegeben werden');
$I->dontSee('Not enough supporters.');
$I->see(mb_strtoupper('Änderungsantrag bestätigen'), 'h1');
