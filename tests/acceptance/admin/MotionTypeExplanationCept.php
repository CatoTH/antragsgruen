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
$I->selectFueluxOption('#pdfLayout', '0');
$I->fillField('#typeMotionPrefix', 'B');
$I->checkOption('.presetApplication');
$I->submitForm('.motionTypeCreateForm', [], 'create');
$I->wait(0.5);

$I->see('Der Antragstyp wurde angelegt. Genauere Einstellungen kannst du nun auf dieser Seite vornehmen.');
$I->seeInField('#typeTitleSingular', 'Bewerbung');

$I->dontSeeElement('#typeCreateExplanation_wysiwyg');
$I->dontSeeCheckboxIsChecked('#typeHasCreateExplanation');
$I->executeJS('$("#typeHasCreateExplanation").prop("checked", true).trigger("change");');
$I->wait(0.5);
$I->seeElement('#typeCreateExplanation_wysiwyg');
$I->executeJS('CKEDITOR.instances.typeCreateExplanation_wysiwyg.setData("<p>This is how you can create an application:</p>");');
$I->submitForm('.adminTypeForm', [], 'save');

$I->wait(0.5);
$I->seeElement('#typeCreateExplanation_wysiwyg');
$I->see('This is how you can create an application', '#typeCreateExplanation_wysiwyg');

$I->gotoConsultationHome();
$I->click('#sidebar .createMotion' . AcceptanceTester::FIRST_FREE_MOTION_TYPE . ' a');
$I->see('This is how you can create an application:');
$I->dontSee('Antrag oder Änderungsantrag?');

$I->gotoConsultationHome();
$I->click('#sidebar .createMotion1');
$I->dontSee('This is how you can create an application:');
$I->see('Antrag oder Änderungsantrag?');

