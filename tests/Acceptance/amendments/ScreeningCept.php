<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('switch to amendment screening mode');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->dontSeeElement('#adminTodo');
$consultationSettingPage = $I->gotoStdAdminPage()->gotoConsultation();
$I->cantSeeCheckboxIsChecked('#screeningAmendments');
$I->checkOption('#screeningAmendments');
$consultationSettingPage->saveForm();
$I->canSeeCheckboxIsChecked('#screeningAmendments');



$I->wantTo('create an amendment as a logged out user');
$page = $I->gotoConsultationHome();
$I->logout();
$page = $page->gotoAmendmentCreatePage(2);
$page->fillInValidSampleData('Neuer Testantrag');

$I->executeJS('window.newText = CKEDITOR.instances.sections_2_wysiwyg.getData();');
$I->executeJS('window.newText = window.newText.replace(/woschechta Bayer/g, "Sauprei&szlig;");');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(window.newText);');
$I->executeJS('CKEDITOR.instances.amendmentReason_wysiwyg.setData("<p>This is my reason</p>");');
$I->fillField('#sections_1', 'Neuer Testantrag');
$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');
$I->see('Er wird nun auf formale Richtigkeit geprüft und dann freigeschaltet.');

$I->wantTo('check that the amendment is not visible yet');
$I->gotoConsultationHome();
$I->dontSeeElement('.amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);

$I->gotoMotion(true, 2);
$I->dontSeeElement('.amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);

$I->wantTo('go to the admin page');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();

$I->click('#adminTodo');
$I->seeElement('.adminTodo .amendmentsScreen' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);


$I->wantTo('Screen the amendment with an invalid title String (race condition)');
$I->click('.adminTodo .amendmentsScreen' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID . ' a');
$I->seeElement('#amendmentScreenForm');
$I->executeJS('$("#amendmentScreenForm input[name=titlePrefix]").attr("value", "Ä2");');
$I->submitForm('#amendmentScreenForm ', [], 'screen');
$I->see('Das angegebene Antragskürzel wird bereits von einem anderen Änderungsantrag verwendet.');


$I->wantTo('screen the amendment normally');
$I->seeElement('#amendmentScreenForm');
$I->submitForm('#amendmentScreenForm', [], 'screen');
$I->see('Der Änderungsantrag wurde freigeschaltet.');


$I->wantTo('check if the amendment is visible now');
$I->gotoConsultationHome();
$I->seeElement('.motionListStd .amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->seeElement('#sidebar ul.amendments .amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
