<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();


$I->wantTo('check that supporters are disabled by deafult');
$I->gotoStdConsultationHome()->gotoMotionCreatePage();
$I->dontSeeElement('.supporterData');



$I->wantTo('enable supporters');
$I->gotoStdConsultationHome();
$I->loginAsStdAdmin();
$motionTypePage = $I->gotoStdAdminPage()->gotoMotionTypes(1);

$I->seeOptionIsSelected('#typeInitiatorForm', \app\models\initiatorForms\OnlyInitiator::getTitle());
$I->selectOption('#typeInitiatorForm', \app\models\initiatorForms\WithSupporters::getTitle());
$I->fillField('#typeMinSupporters', 0);
$I->uncheckOption('#typeSupportersOrgaRow input[type=checkbox]');

$motionTypePage->saveForm();
$I->seeOptionIsSelected('#typeInitiatorForm', \app\models\initiatorForms\WithSupporters::getTitle());



$I->wantTo('create a simple motion with standard settings');
$createPage = $I->gotoStdConsultationHome()->gotoMotionCreatePage();
$I->seeElement('.supporterData');
$I->seeElement('.supporterData input.name');
$I->dontSeeElement('.supporterData input.organization');

$createPage->fillInValidSampleData('Sample motion with supporters');
$createPage->saveForm();

$I->see(mb_strtoupper('Antrag bestätigen'), 'h1');



$I->wantTo('set more restrictive settings');
$I->gotoStdConsultationHome();
$motionTypePage = $I->gotoStdAdminPage()->gotoMotionTypes(1);

$I->fillField('#typeMinSupporters', 2);
$I->checkOption('#typeSupportersOrgaRow input[type=checkbox]');
$motionTypePage->saveForm();
$I->seeInField('#typeMinSupporters', 2);



$I->wantTo('create a motion, but without supporters');
$consHome = $I->gotoStdConsultationHome();
$I->logout();
$createPage = $consHome->gotoMotionCreatePage();
$createPage->fillInValidSampleData('Another sample motion with supporters');

$createPage->saveForm();

$I->wait(1);
$I->dontSee(mb_strtoupper('Antrag bestätigen'), 'h1');
$I->see('Es müssen mindestens 2 UnterstützerInnen angegeben werden');

$I->click('.bootbox.modal .btn-primary');
$I->wait(1);
$I->dontSee('Es müssen mindestens 2 UnterstützerInnen angegeben werden');



$I->wantTo('remove and add some rows');
$lineNumbers = $I->executeJS('
    $(".supporterData .supporterRow").eq(1).remove();
    $(".supporterData .supporterRow").eq(1).remove();
    $(".supporterData .adderRow a").click();
    return $(".supporterData .supporterRow").length;
');
if ($lineNumbers != 2) {
    $I->see('an invalid number of supporter rows: ' . $lineNumbers . ' (should be: 2)');
}



$I->wantTo('fill in correct data');
$I->executeJS('
    $(".supporterData .supporterRow").eq(0).find("input.name").val("Name 1");
    $(".supporterData .supporterRow").eq(0).find("input.organization").val("Orga 1");
    $(".supporterData .supporterRow").eq(1).find("input.name").val("Name 2");
');
$createPage->saveForm();

$I->see(mb_strtoupper('Antrag bestätigen'), 'h1');
$I->see('Name 1');
$I->see('Orga 1');
$I->see('Name 2');

$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->see(mb_strtoupper('Antrag eingereicht'), 'h1');




// @TODO Screening the motion and verifying the data is visible
