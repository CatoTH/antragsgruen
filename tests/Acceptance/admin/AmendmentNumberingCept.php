<?php

/** @var \Codeception\Scenario $scenario */
use app\models\amendmentNumbering\ByLine;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$consultation = $I->loginAndGotoStdAdminPage()->gotoConsultation();
$consultation->selectAmendmentNumbering(ByLine::getID());
$consultation->saveForm();

$I->gotoConsultationHome();
$I->gotoMotion(true, 2);
$I->click('.sidebarActions .amendmentCreate a');
$I->wait(1);

$I->executeJS('window.newText = CKEDITOR.instances.sections_2_wysiwyg.getData();');
$I->executeJS('window.newText = window.newText.replace(/woschechta Bayer/g, "Sauprei&szlig;");');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(window.newText);');
$I->executeJS('CKEDITOR.instances.amendmentReason_wysiwyg.setData("<p>This is my reason</p>");');

$I->wait(1);
$I->fillField('#initiatorPrimaryName', 'My Name');
$I->fillField('#initiatorEmail', 'test@example.org');

$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');

$I->gotoConsultationHome();
$I->see('A2-003', '.amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
