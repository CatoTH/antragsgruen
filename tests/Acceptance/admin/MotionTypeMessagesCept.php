<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('create a new motion type from a template');
$I->loginAndGotoStdAdminPage();
$I->click('.motionTypeCreate a');

$I->fillField('#typeTitleSingular', 'Bewerbung');
$I->fillField('#typeTitlePlural', 'Bewerbungen');
$I->fillField('#typeCreateTitle', 'Bewirb dich!');
$I->fillField('#typeMotionPrefix', 'B');
$I->checkOption('.presetApplication');
$I->submitForm('.motionTypeCreateForm', [], 'create');
$I->wait(0.5);

$I->see('Der Antragstyp wurde angelegt. Genauere Einstellungen kannst du nun auf dieser Seite vornehmen.');
$I->seeInField('#typeTitleSingular', 'Bewerbung');


$I->wantTo('change the motion type text');
$I->click('.motionTypeTranslations');
$I->seeElement('#string_motion_create_explanation');
$I->fillField('#string_motion_create_explanation', '<ul><li>This is how you can create an application:</li>');
$I->submitForm('#translationForm', [], 'save');
$I->seeInField('#string_motion_create_explanation', '<ul><li>This is how you can create an application:</li></ul>');

$I->wantTo('see the changed text on the page');
$I->gotoConsultationHome();
$I->click('#sidebar .createMotion' . AcceptanceTester::FIRST_FREE_MOTION_TYPE . ' a');
$I->see('This is how you can create an application:');
$I->dontSee('Antrag oder Änderungsantrag?');

$I->gotoConsultationHome();
$I->click('#sidebar .createMotion1');
$I->dontSee('This is how you can create an application:');
$I->see('Antrag oder Änderungsantrag?');

$I->wantTo('reset it to the original text');
$I->gotoStdAdminPage();
$I->click('#translationLink');
$I->click('.motionTypeTranslation' . AcceptanceTester::FIRST_FREE_MOTION_TYPE);
$I->fillField('#string_motion_create_explanation', '');
$I->submitForm('#translationForm', [], 'save');
$I->seeInField('#string_motion_create_explanation', '');
$I->gotoConsultationHome();
$I->click('#sidebar .createMotion' . AcceptanceTester::FIRST_FREE_MOTION_TYPE . ' a');
$I->see('Antrag oder Änderungsantrag?');
