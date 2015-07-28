<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('make sure the supporter-warning appears for natural persons');

$page = $I->gotoConsultationHome(true, 'bdk', 'bdk');
$I->loginAsStdAdmin();
$I->click('.motionLink4');
$I->click('.sidebarActions .amendmentCreate a');

$I->fillField(['name' => 'Initiator[name]'], 'Mein Name');
$I->fillField(['name' => 'Initiator[contactEmail]'], 'test@example.org');
$I->submitForm('#amendmentEditForm', [], 'save');

$I->wait(1);

$I->see('Es müssen mindestens 19 UnterstützerInnen angegeben werden', '.bootbox');
$I->click('.bootbox .btn-primary');

$I->wait(1);



$I->wantTo('make sure it does not appear for organizations');

$I->selectOption('#personTypeOrga', \app\models\db\ISupporter::PERSON_ORGANIZATION);
$I->submitForm('#amendmentEditForm', [], 'save');

$I->wait(1);

$I->see('Es muss ein Beschlussdatum angegeben werden', '.bootbox');
$I->click('.bootbox .btn-primary');

$I->wait(1);


$I->fillField('#resolutionDate', '01.01.2000');
$I->submitForm('#amendmentEditForm', [], 'save');

$I->wait(1);
$I->dontSee('Es müssen mindestens 19 UnterstützerInnen angegeben werden', '.bootbox');
$I->dontSee('Not enough supporters.');
$I->see(mb_strtoupper('Änderungsantrag bestätigen'), 'h1');
