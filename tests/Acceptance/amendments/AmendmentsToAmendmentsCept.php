<?php

/** @var \Codeception\Scenario $scenario */
use app\models\settings\Consultation;
use Tests\_pages\AmendmentCreatePage;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('enable amendments to amendments');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->dontSeeElement('#sidebar .amendmentCreate');
$page = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->checkOption('#allowAmendmentsToAmendments');
$page->saveForm();


$I->wantTo('create an amendment to an amendment');
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->click('#sidebar .amendmentCreate');

$I->wait(0.3);
$I->see('A small replacement', '#sections_2_wysiwyg .ice-ins');
$I->see('At vero', '#sections_2_wysiwyg .ice-del');
$I->dontSee('The first amendment');

$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(CKEDITOR.instances.sections_2_wysiwyg.getData().replace(/Stet clita kasd gubergren/, "Test 12345678"))');
$I->executeJs('CKEDITOR.instances.amendmentReason_wysiwyg.setData("The follow-up amendment");');
$I->fillField('#initiatorPrimaryName', 'A new person');
$I->fillField('#initiatorEmail', 'test@example.org');

$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');


$I->wantTo('see the new amendment');
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->see('Ä5', '.amendments .amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->click('.amendments .amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->see('Ä1', '.amendingAmendmentRow');
$I->see('Test 12345678', 'ins');
$I->see('A small replacement', 'ins');
$I->see('The follow-up amendment');


$I->wantTo('switch the home page to an agenda-based layout');
$page = $I->gotoStdAdminPage()->gotoAppearance();
$I->selectOption('#startLayoutType', Consultation::START_LAYOUT_AGENDA_LONG);
$page->saveForm();

$I->gotoConsultationHome();
$I->click('.agendaEditLink');
$I->wait(0.5);
$listData = ['items' => [[
    'type'     => 'item',
    'code'     => null,
    'title'    => 'Amendments',
    'settings' => ['has_speaking_list' => false, 'in_proposed_procedures' => true, 'motion_types' => []],
    'children' => [],
]]];
$I->executeJs('agendaWidget.$refs["agenda-edit-widget"].setAgendaTest(' . json_encode($listData) . ');');
$I->wait(0.3);
$I->clickJS('.agendaEditWidget .btnSave');
$I->wait(1);


// An amendment to an amendment can carry an agendaItemId of its own - amendment/create accepts one next to
// createFromAmendment. It still belongs below the amendment it amends, and must not be listed at the agenda
// item as if it were a motion of its own.
$I->wantTo('create an amendment to an amendment that is assigned to the agenda item');
$I->openPage(AmendmentCreatePage::class, [
    'subdomain'           => 'stdparteitag',
    'consultationPath'    => 'std-parteitag',
    'motionSlug'          => 'Testing_proposed_changes-630',
    'createFromAmendment' => 279,
    'agendaItemId'        => AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID,
]);
$I->wait(0.3);
$I->executeJs('CKEDITOR.instances.amendmentReason_wysiwyg.setData("Assigned to the agenda item");');
$I->fillField('#initiatorPrimaryName', 'A new person');
$I->fillField('#initiatorEmail', 'test@example.org');
$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');


$I->wantTo('see it nested below the amendment it amends, not as a motion of its own');
$secondAmendment = AcceptanceTester::FIRST_FREE_AMENDMENT_ID + 1;
$I->gotoConsultationHome();

// Not a top-level entry of the agenda item, and not one of the "other motions" either
$I->dontSeeElement('.motionListBelowAgenda > li.motion.amendmentRow' . $secondAmendment);
// ...but still reachable, below the motion it belongs to
$I->seeElement('.motionListBelowAgenda .motion .amendments .amendmentRow' . $secondAmendment);
