<?php

/** @var \Codeception\Scenario $scenario */
use app\models\db\ISupporter;
use app\models\supportTypes\SupportBase;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('check that supporters are disabled by default');
$I->gotoConsultationHome()->gotoMotionCreatePage();
$I->dontSeeElement('.supporterData');



$I->wantTo('enable supporters');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$motionTypePage = $I->gotoStdAdminPage()->gotoMotionTypes(1);

$I->selectOption('#typeSupportType', SupportBase::GIVEN_BY_INITIATOR);
$I->fillField('#typeMinSupporters', 0);
$I->uncheckOption('#typeHasOrga');

$motionTypePage->saveForm();



$I->wantTo('create a simple motion with standard settings');
$createPage = $I->gotoConsultationHome()->gotoMotionCreatePage();
$I->seeElement('.supporterData');
$I->seeElement('.supporterData input.name');
$I->dontSeeElement('.supporterData input.organization');

$createPage->fillInValidSampleData('Sample motion with supporters');
$createPage->saveForm();

$I->see(mb_strtoupper('Antrag bestätigen'), 'h1');



$I->wantTo('set more restrictive settings');
$I->gotoConsultationHome();
$motionTypePage = $I->gotoStdAdminPage()->gotoMotionTypes(1);

$I->fillField('#typeMinSupporters', 2);
$I->checkOption('#typeAllowMoreSupporters');
$I->checkOption('#typeHasOrga');
$motionTypePage->saveForm();
$I->seeInField('#typeMinSupporters', '2');


$I->wantTo('create a motion, but without supporters');
$consHome = $I->gotoConsultationHome();
$I->logout();
$createPage = $consHome->gotoMotionCreatePage();


$I->wantTo('test persons and organizations');
$I->seeElement('.supporterDataHead');
$I->seeElement('.supporterData');
//$I->seeElement('.initiatorData .adderRow');
$I->seeElement('#initiatorOrga');
$I->dontSeeElement('#resolutionDate');
$I->selectOption('#personTypeOrga', (string)ISupporter::PERSON_ORGANIZATION);
$I->dontSeeElement('.supporterDataHead');
$I->dontSeeElement('.supporterData');
//$I->dontSeeElement('.initiatorData .adderRow');
$I->dontSeeElement('#initiatorOrga');
$I->seeElement('#resolutionDate');
$I->selectOption('#personTypeNatural', (string)ISupporter::PERSON_NATURAL);
$I->seeElement('.supporterDataHead');
$I->seeElement('.supporterData');
//$I->seeElement('.initiatorData .adderRow');
$I->seeElement('#initiatorOrga');
$I->dontSeeElement('#resolutionDate');




$I->wantTo('fill in some data, but no supporters');
$createPage->fillInValidSampleData('Another sample motion with supporters');

$createPage->saveForm();

$I->wait(1);
$I->dontSee(mb_strtoupper('Antrag bestätigen'), 'h1');
$I->see('Es müssen mindestens 2 Unterstützer*innen angegeben werden');
$I->acceptBootboxAlert();
$I->dontSee('Es müssen mindestens 2 Unterstützer*innen angegeben werden');





$I->wantTo('remove and add some supporter rows');
$lineNumbers = $I->executeJS('
    $(".supporterData .supporterRow").eq(1).remove();
    $(".supporterData .supporterRow").eq(1).remove();
    $(".supporterData .adderRow button").click();
    return $(".supporterData .supporterRow").length;
');
if ($lineNumbers != 2) {
    $I->fail('an invalid number of supporter rows: ' . $lineNumbers . ' (should be: 2)');
}


$I->wantTo('fill in correct data');
$I->executeJS('
    $(".supporterData .supporterRow").eq(0).find("input.name").val("Name 1");
    $(".supporterData .supporterRow").eq(0).find("input.organization").val("Orga 1");
    $(".supporterData .supporterRow").eq(1).find("input.name").val("Name 2");

    //$(".initiatorData .initiatorRow").eq(0).find("input.name").val("Initiator 2");
');
$createPage->saveForm();

$I->see(mb_strtoupper('Antrag bestätigen'), 'h1');
$I->see('Name 1');
$I->see('Orga 1');
$I->see('Name 2');
//$I->see('Initiator 2');





$I->wantTo('modify the supporters');
$I->submitForm('#motionConfirmForm', [], 'modify');
//$I->seeInField(['name' => 'moreInitiators[name][]'], 'Initiator 2');
$val = $I->executeJS('return $(".supporterData .supporterRow").eq(0).find("input.name").val()');
if ($val!=='Name 1') {
    $I->fail('an invalid content of field 1: ' . $val . ' (should be: Name 1)');
}
$val = $I->executeJS('return $(".supporterData .supporterRow").eq(1).find("input.name").val()');
if ($val!=='Name 2') {
    $I->fail('an invalid content of field 2: ' . $val . ' (should be: Name 2)');
}
$val = $I->executeJS('return $(".supporterData .supporterRow").eq(0).find("input.organization").val()');
if ($val!=='Orga 1') {
    $I->fail('an invalid content of orga 1: ' . $val . ' (should be: Orga 1)');
}

$I->executeJS('
    $(".supporterData .supporterRow").eq(0).find("input.name").val("Person 1");
    $(".supporterData .supporterRow").eq(0).find("input.organization").val("Organization 1");
    $(".supporterData .supporterRow").eq(1).find("input.name").val("Person 2");

    //$(".initiatorData .initiatorRow").eq(0).find("input.name").val("Another Initiator");
');
$createPage->saveForm();

$I->see(mb_strtoupper('Antrag bestätigen'), 'h1');
$I->dontSee('Name 1');
$I->dontSee('Orga 1');
$I->dontSee('Name 2');
//$I->dontSee('Initiator 2');
$I->see('Person 1');
$I->see('Organization 1');
$I->see('Person 2');
//$I->see('Another Initiator');




$I->wantTo('submit the motion');

$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->see(mb_strtoupper('Antrag veröffentlicht'), 'h1');
$I->submitForm('#motionConfirmedForm', [], '');

$I->see('Another sample motion with supporters');



$I->wantTo('verify the new supporters are visible');
$I->see('Mein Name');
//$I->see('Another Initiator');
$I->click('.motionLink' . (AcceptanceTester::FIRST_FREE_MOTION_ID + 1));

//$I->see('Another Initiator', '.motionData');
$I->see('Mein Name', '.motionData');
$I->see('Person 1', '.supporters');
$I->see('Organization 1', '.supporters');
$I->see('Person 2', '.supporters');
