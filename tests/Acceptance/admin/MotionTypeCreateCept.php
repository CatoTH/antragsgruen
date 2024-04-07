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

$I->see('Der Antragstyp wurde angelegt. Genauere Einstellungen kannst du nun auf dieser Seite vornehmen.');
$I->seeInField('#typeTitleSingular', 'Bewerbung');
$I->seeInField('#typeTitlePlural', 'Bewerbungen');
$I->seeInField('#typeCreateTitle', 'Bewirb dich!');
$I->seeInField('#typeMotionPrefix', 'B');
$I->seeInField('.section' . AcceptanceTester::FIRST_FREE_MOTION_SECTION . ' .sectionTitle input', 'Name');
$I->seeInField('.section' . (AcceptanceTester::FIRST_FREE_MOTION_SECTION + 1) . ' .sectionTitle input', 'Foto');

$I->wantTo('create another motion type');
$I->click('#adminLink');
$I->click('.motionTypeCreate a');
$I->fillField('#typeTitleSingular', 'Abc1');
$I->fillField('#typeTitlePlural', 'Abc2');
$I->fillField('#typeCreateTitle', 'Create type 2');
$I->fillField('#typeMotionPrefix', 'C');
$I->checkOption('.preset' . AcceptanceTester::FIRST_FREE_MOTION_TYPE);
$I->submitForm('.motionTypeCreateForm', [], 'create');

$I->see('Der Antragstyp wurde angelegt. Genauere Einstellungen kannst du nun auf dieser Seite vornehmen.');
$I->seeInField('#typeTitleSingular', 'Abc1');
$I->seeInField('#typeTitlePlural', 'Abc2');
$I->seeInField('#typeCreateTitle', 'Create type 2');
$I->seeInField('#typeMotionPrefix', 'C');
$I->seeInField('.section' . (AcceptanceTester::FIRST_FREE_MOTION_SECTION + 5) . ' .sectionTitle input', 'Name');
$I->seeInField('.section' . (AcceptanceTester::FIRST_FREE_MOTION_SECTION + 6) . ' .sectionTitle input', 'Foto');

$I->wantTo('highlight the create link in a big, pink button');
$I->checkOption('#typeCreateSidebar');
$I->submitForm('.adminTypeForm', [], 'save');

$I->wantTo('check if I can see the new types');
$I->gotoConsultationHome();
$I->seeElement('#sidebar .createMotionHolder1 .createMotion1');
$I->dontSeeElement('#sidebar .createMotionList .createMotion1');
$I->dontSeeElement('#sidebar .createMotionHolder1 .createMotion' . AcceptanceTester::FIRST_FREE_MOTION_TYPE);
$I->seeElement('#sidebar .createMotionList .createMotion' . AcceptanceTester::FIRST_FREE_MOTION_TYPE);
$I->seeElement('#sidebar .createMotionHolder1 .createMotion' . (AcceptanceTester::FIRST_FREE_MOTION_TYPE + 1));
$I->dontSeeElement('#sidebar .createMotionList .createMotion' . (AcceptanceTester::FIRST_FREE_MOTION_TYPE + 1));

$I->click('#sidebar .createMotion' . (AcceptanceTester::FIRST_FREE_MOTION_TYPE + 1));
$I->see('Alter');
$I->click('#adminLink');
$I->seeElement('.motionType' . AcceptanceTester::FIRST_FREE_MOTION_TYPE);
$I->seeElement('.motionType' . (AcceptanceTester::FIRST_FREE_MOTION_TYPE + 1));


$I->wantTo('delete the first motion type again');
$I->click('.motionType' . AcceptanceTester::FIRST_FREE_MOTION_TYPE);
$I->dontSeeElement('.deleteTypeForm');
$I->click('.deleteTypeOpener button');
$I->dontSeeElement('.deleteTypeOpener');
$I->seeElement('.deleteTypeForm');
$I->submitForm('.deleteTypeForm', [], 'delete');
$I->see('Der Antragstyp wurde erfolgreich gelöscht.');
$I->click('#adminLink');
$I->dontSeeElement('.motionType' . AcceptanceTester::FIRST_FREE_MOTION_TYPE);
$I->seeElement('.motionType' . (AcceptanceTester::FIRST_FREE_MOTION_TYPE + 1));
$I->gotoConsultationHome();
$I->dontSeeElement('#sidebar .createMotion' . AcceptanceTester::FIRST_FREE_MOTION_TYPE);


$I->wantTo('delete the original motion type - should not work');
$I->click('#adminLink');
$I->click('.motionType1');
$I->dontSeeElement('.deleteTypeForm');
$I->click('.deleteTypeOpener button');
$I->dontSeeElement('.deleteTypeOpener');
$I->see('Dieser Antragstyp kann (noch) nicht gelöscht werden');


$I->wantTo('create a motion type without template');
$I->click('#adminLink');
$I->click('.motionTypeCreate a');
$I->fillField('#typeTitleSingular', 'Abc1');
$I->fillField('#typeTitlePlural', 'Abc2');
$I->fillField('#typeCreateTitle', 'Abc3');
$I->fillField('#typeMotionPrefix', 'C');
$I->checkOption('.presetNone');
$I->submitForm('.motionTypeCreateForm', [], 'create');

$I->see('Der Antragstyp wurde angelegt. Genauere Einstellungen kannst du nun auf dieser Seite vornehmen.');
