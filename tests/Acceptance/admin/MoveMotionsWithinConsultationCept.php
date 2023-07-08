<?php

/** @var \Codeception\Scenario $scenario */

use app\models\settings\Consultation;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->loginAsStdAdmin();

$I->wantTo('prepare the test case');
$I->gotoMotion(true, 'Testing_proposed_changes-630');
$I->fillField('#comment_-1_-1_text', 'A motion comment');
$I->submitForm('#comment_-1_-1_form', [], 'writeComment');
$I->see('A motion comment');
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->fillField('#comment_-1_-1_text', 'An amendment comment');
$I->submitForm('#comment_-1_-1_form', [], 'writeComment');
$I->see('An amendment comment');

$page = $I->gotoStdAdminPage()->gotoAppearance();
$I->selectOption('#startLayoutType', Consultation::START_LAYOUT_AGENDA_LONG);
$page->saveForm();

$I->gotoConsultationHome();

$I->executeJS('$(".agendaItemAdder").last().find("a.addEntry").click()');
$I->executeJS('$(".agendaItemEditForm").last().find(".title input").val("Earth");');
$I->executeJS('$(".agendaItemEditForm").last().trigger("submit");');

$I->executeJS('$(".agendaItemAdder").last().find("a.addEntry").click()');
$I->executeJS('$(".agendaItemEditForm").last().find(".title input").val("Mars");');
$I->executeJS('$(".agendaItemEditForm").last().trigger("submit");');

$I->executeJS('$(".agendaItemAdder").last().find("a.addEntry").click()');
$I->executeJS('$(".agendaItemEditForm").last().find(".title input").val("venus");');
$I->executeJS('$(".agendaItemEditForm").last().trigger("submit");');

$earth = AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID;
$mars = AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID + 1;
$venus = AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID + 2;

$I->gotoMotionList()->gotoMotionEdit(118);
$I->selectOption('#agendaItemId', $earth);
$I->submitForm('#motionUpdateForm', [], 'save');

$I->gotoConsultationHome();
$I->seeElement('.agenda' . $earth . ' .motionRow118');

$I->wantTo('finally move it, without reference');
$I->gotoMotionList()->gotoMotionEdit(118);
$I->click('.sidebarActions .move');
usleep(500000);
$I->dontSeeElement('.moveToAgendaItem');
$I->checkOption("//input[@name='operation'][@value='move']");
$I->seeCheckboxIsChecked("//input[@name='operation'][@value='move']");
$I->checkOption("//input[@name='target'][@value='agenda']");
$I->seeElement('.moveToAgendaItem');
$I->selectOption('#agendaItemId1', $mars);
$I->wait(0.3);
$I->submitForm('.adminMoveForm', [], 'move');

$I->see('A8: Testing proposed changes', 'h1');
$I->see('2. Mars', '.motionDataTable');

$I->gotoConsultationHome();
$I->dontSeeElement('.agenda' . $earth);
$I->seeElement('.agenda' . $mars . ' .motionRow118');


$I->wantTo('finally move it, with reference');
$I->gotoMotionList()->gotoMotionEdit(118);
$I->click('.sidebarActions .move');
$I->dontSeeElement('.moveToAgendaItem');
$I->fillField('#motionTitlePrefix', 'A8M');
$I->checkOption("//input[@name='operation'][@value='copy']");
$I->checkOption("//input[@name='target'][@value='agenda']");
$I->seeElement('.moveToAgendaItem');
$I->selectOption('#agendaItemId1', $venus);
$I->submitForm('.adminMoveForm', [], 'move');

$I->see('A8M: Testing proposed changes', 'h1');
$I->see('3. Venus', '.motionDataTable');
$I->see('A motion comment');


$I->gotoConsultationHome();
$I->dontSeeElement('.agenda' . $earth);
$I->seeElement('.agenda' . $mars . ' .motionRow118.moved');
$I->seeElement('.agenda' . $venus . ' .motionRow' . AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->see('A8M', '.motionRow' . AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->click('.motionRow' . AcceptanceTester::FIRST_FREE_MOTION_ID . ' .amendmentRow' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID . ' .amendmentTitle');

$I->see('A small replacement', 'ins');
$I->see('Von Zeile 7 bis 9:');
$I->see('An amendment comment');


$I->click('#motionListLink');
$I->click('#exportProcedureBtn');
$I->click('.linkProcedureIntern');

$I->seeElement('.motion118.moved');
$I->see('Verschoben', '.motion118');
$I->click('.motion118 .moved a');
$I->see('A8M: Testing proposed changes', 'h1');


$I->gotoMotion(true, AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->click('#sidebar .mergeamendments a');
$I->wait(0.2);
$I->executeJS('$(".toMergeAmendments .selectAll").trigger("click");');
$I->submitForm('.mergeAllRow', [], null);
$I->wait(0.5);
$I->see('A big replacement', '#paragraphWrapper_2_1 .collidingParagraph');


$I->wantTo('copy the new motion within the consultation');
$I->gotoMotion(true, AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->click('#sidebar .adminEdit a');
$I->click('.sidebarActions .move');

$I->dontSeeElement('.moveToConsultationItem');
$I->dontSeeElement('.targetSame');
$I->checkOption("//input[@name='operation'][@value='copynoref']");
$I->seeCheckboxIsChecked("//input[@name='operation'][@value='copynoref']");
$I->wait(0.1);
$I->seeElement('.targetSame');
$I->checkOption("//input[@name='target'][@value='same']");
$I->dontSeeElement('.moveToConsultationItem');
$I->wait(0.1);
$I->seeElement('.prefixAlreadyTaken');

$I->fillField('#motionTitlePrefix', 'N1.2');
$I->wait(0.1);
$I->dontSeeElement('.prefixAlreadyTaken');

$I->submitForm('.adminMoveForm', [], 'move');
$I->see('N1.2', 'h1');
$I->dontSeeElement('.motionDataTable .motionHistory');
