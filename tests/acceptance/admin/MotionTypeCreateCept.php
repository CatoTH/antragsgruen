<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('create a new motion type from a template');
$I->loginAndGotoStdAdminPage();
$I->click('.motionTypeCreate a');

$I->fillField('#typeTitleSingular', 'Bewerbung');
$I->fillField('#typeTitlePlural', 'Bewerbungen');
$I->fillField('#typeCreateTitle', 'Bewirb dich!');
$I->selectOption('#pdfLayout', 'LDK Bayern');
$I->fillField('#typeMotionPrefix', 'B');
$I->checkOption('.presetApplication');
$I->submitForm('.motionTypeCreateForm', [], 'create');

$I->see('Der Antragstyp wurde angelegt. Genauere Einstellungen kannst du nun auf dieser Seite vornehmen.');
$I->seeInField('#typeTitleSingular', 'Bewerbung');
$I->seeInField('#typeTitlePlural', 'Bewerbungen');
$I->seeInField('#typeCreateTitle', 'Bewirb dich!');
$I->seeInField('#typeMotionPrefix', 'B');
$I->seeInField('.section29 .sectionTitle input', 'Name');
$I->seeInField('.section30 .sectionTitle input', 'Foto');



$I->wantTo('create another motion type');
$I->click('#adminLink');
$I->click('.motionTypeCreate a');
$I->fillField('#typeTitleSingular', 'Abc1');
$I->fillField('#typeTitlePlural', 'Abc2');
$I->fillField('#typeCreateTitle', 'Abc3');
$I->selectOption('#pdfLayout', 'DBJR');
$I->fillField('#typeMotionPrefix', 'C');
$I->checkOption('.preset10');
$I->submitForm('.motionTypeCreateForm', [], 'create');

$I->see('Der Antragstyp wurde angelegt. Genauere Einstellungen kannst du nun auf dieser Seite vornehmen.');
$I->seeInField('#typeTitleSingular', 'Abc1');
$I->seeInField('#typeTitlePlural', 'Abc2');
$I->seeInField('#typeCreateTitle', 'Abc3');
$I->seeInField('#typeMotionPrefix', 'C');
$I->seeInField('.section33 .sectionTitle input', 'Name');
$I->seeInField('.section34 .sectionTitle input', 'Foto');


$I->wantTo('check if I can see the new types');
$I->gotoConsultationHome();
$I->seeElement('#sidebar .createMotion10');
$I->seeElement('#sidebar .createMotion11');
$I->click('#sidebar .createMotion11 a');
$I->see('Geschlecht');
$I->see('Alter');
$I->click('#adminLink');
$I->seeElement('.motionTypeSection10');
$I->seeElement('.motionTypeSection11');


$I->wantTo('delete the first motion type again');
$I->click('.motionType10');
$I->dontSeeElement('.deleteTypeForm');
$I->click('.deleteTypeOpener a');
$I->dontSeeElement('.deleteTypeOpener');
$I->seeElement('.deleteTypeForm');
$I->submitForm('.deleteTypeForm', [], 'delete');
$I->see('Der Antragstyp wurde erfolgreich gelöscht.');
$I->click('#adminLink');
$I->dontSeeElement('.motionTypeSection10');
$I->seeElement('.motionTypeSection11');
$I->gotoConsultationHome();
$I->dontSeeElement('#sidebar .createMotion10');


$I->wantTo('delete the original motion type - should not work');
$I->click('#adminLink');
$I->click('.motionType1');
$I->dontSeeElement('.deleteTypeForm');
$I->click('.deleteTypeOpener a');
$I->dontSeeElement('.deleteTypeOpener');
$I->see('Dieser Antragstyp kann (noch) nicht gelöscht werden');
