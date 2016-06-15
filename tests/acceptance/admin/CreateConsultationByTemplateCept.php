<?php

/** @var \Codeception\Scenario $scenario */
use app\tests\_pages\ConsultationHomePage;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->loginAndGotoStdAdminPage()->gotoConsultationCreatePage();

$I->see('Standard-Veranstaltung', '.consultation1');

$I->wantTo('create a new consultation');
$I->fillField('#newTitle', 'Neue Veranstaltung 1');
$I->fillField('#newShort', 'NeuKurz');
$I->fillField('#newPath', 'neukurz');
$I->uncheckOption('#newSetStandard');
$I->submitForm('.consultationCreateForm', [], 'createConsultation');

$I->see('Die neue Veranstaltung wurde angelegt.');
$I->see('Neue Veranstaltung 1', '.consultation' . AcceptanceTester::FIRST_FREE_CONSULTATION_ID);
$I->see('Standard-Veranstaltung', '.consultation1');


$I->wantTo('check that the motion types where cloned successfully');
$I->gotoStdAdminPage('stdparteitag', 'neukurz')->gotoMotionTypes(AcceptanceTester::FIRST_FREE_MOTION_TYPE);
$I->seeNumberOfElements('#sectionsList > li', 5);



ConsultationHomePage::openBy($I, [
    'subdomain' => 'stdparteitag'
]);
$I->see('Test2', 'h1');


$I->wantTo('create the same again, should not work');

$I->gotoStdAdminPage()->gotoConsultationCreatePage();

$I->fillField('#newTitle', 'Neue Veranstaltung 2');
$I->fillField('#newShort', 'NeuKurz 2');
$I->fillField('#newPath', 'neukurz');
$I->uncheckOption('#newSetStandard');
$I->submitForm('.consultationCreateForm', [], 'createConsultation');

$I->see('Diese Adresse ist leider schon von einer anderen Veranstaltung auf dieser Seite vergeben.');


$I->wantTo('create a new standard consultation');

$I->fillField('#newTitle', 'Noch eine neue Veranstaltung');
$I->fillField('#newShort', 'NeuKurz2');
$I->fillField('#newPath', 'neukurz2');
$I->checkOption('#newSetStandard');
$I->submitForm('.consultationCreateForm', [], 'createConsultation');

$I->see('Die neue Veranstaltung wurde angelegt.');
$I->see('Noch eine neue Veranstaltung', '.consultation' . (AcceptanceTester::FIRST_FREE_CONSULTATION_ID + 1));
$I->see('Standard-Veranstaltung', '.consultation' . (AcceptanceTester::FIRST_FREE_CONSULTATION_ID + 1));

ConsultationHomePage::openBy($I, [
    'subdomain' => 'stdparteitag'
]);
$I->see('Noch eine neue Veranstaltung', 'h1');


$I->wantTo('set another consultation as standard');

$I->gotoStdAdminPage()->gotoConsultationCreatePage();

$I->click('.consultation' . AcceptanceTester::FIRST_FREE_CONSULTATION_ID . ' .stdbox button');
$I->see('Die Veranstaltung wurde als Standard-Veranstaltung festgelegt.');

ConsultationHomePage::openBy($I, [
    'subdomain' => 'stdparteitag'
]);
$I->see('Neue Veranstaltung 1', 'h1');
