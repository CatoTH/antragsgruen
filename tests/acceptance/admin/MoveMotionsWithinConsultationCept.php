<?php

/** @var \Codeception\Scenario $scenario */

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->loginAsStdAdmin();

$I->wantTo('prepare the test case');
$I->gotoMotion();
$I->fillField('#comment_-1_-1_text', 'A motion comment');
$I->submitForm('#comment_-1_-1_form', [], 'writeComment');
$I->see('A motion comment');
$I->gotoAmendment();
$I->fillField('#comment_-1_-1_text', 'An amendment comment');
$I->submitForm('#comment_-1_-1_form', [], 'writeComment');
$I->see('An amendment comment');

$page = $I->gotoStdAdminPage()->gotoConsultation();
$I->selectFueluxOption('#startLayoutType', '4');
$page->saveForm();

$I->gotoConsultationHome();

$I->executeJS('$(".agendaItemAdder").last().find("a").click()');
$I->executeJS('$(".agendaItemEditForm").last().find(".title").val("Earth");');
$I->executeJS('$(".agendaItemAdder").last().find("a").click()');
$I->executeJS('$(".agendaItemEditForm").last().find(".title").val("Mars");');
$I->executeJS('$(".agendaItemAdder").last().find("a").click()');
$I->executeJS('$(".agendaItemEditForm").last().find(".title").val("venus");');
$I->submitForm('#agendaEditSavingHolder', [], 'saveAgenda');

$earth = AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID;
$mars = AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID + 1;
$venus = AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID + 2;

$I->gotoMotionList()->gotoMotionEdit(2);
$I->selectFueluxOption('#agendaItemId', $earth);
$I->submitForm('#motionUpdateForm', [], 'save');

$I->gotoConsultationHome();
$I->seeElement('.agenda' . $earth . ' .motionRow2');

$I->wantTo('finally move it, without reference');
$I->gotoMotionList()->gotoMotionEdit(2);
$I->click('.sidebarActions .move');
$I->dontSeeElement('.moveToAgendaItem');
$I->checkOption("//input[@name='operation'][@value='move']");
$I->seeCheckboxIsChecked("//input[@name='operation'][@value='move']");
$I->checkOption("//input[@name='target'][@value='agenda']");
$I->seeElement('.moveToAgendaItem');
$I->selectFueluxOption('#agendaItemId1', $mars);
$I->submitForm('.adminMoveForm', [], 'move');

$I->see('A2: O’zapft is!', 'h1');
$I->see('2. Mars', '.motionDataTable');

$I->gotoConsultationHome();
$I->dontSeeElement('.agenda' . $earth);
$I->seeElement('.agenda' . $mars . ' .motionRow2');


$I->wantTo('finally move it, with reference');
$I->gotoMotionList()->gotoMotionEdit(2);
$I->click('.sidebarActions .move');
$I->dontSeeElement('.moveToAgendaItem');
$I->fillField('#motionTitlePrefix', 'A2M');
$I->checkOption("//input[@name='operation'][@value='copy']");
$I->checkOption("//input[@name='target'][@value='agenda']");
$I->seeElement('.moveToAgendaItem');
$I->selectFueluxOption('#agendaItemId1', $venus);
$I->submitForm('.adminMoveForm', [], 'move');

$I->see('A2M: O’zapft is!', 'h1');
$I->see('3. Venus', '.motionDataTable');
$I->see('A motion comment');


$I->gotoConsultationHome();
$I->dontSeeElement('.agenda' . $earth);
$I->seeElement('.agenda' . $mars . ' .motionRow2.moved');
$I->seeElement('.agenda' . $venus . ' .motionRow' . AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->see('A2M', '.motionRow' . AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->click('.motionRow' . AcceptanceTester::FIRST_FREE_MOTION_ID . ' .amendmentRow' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID . ' .amendmentTitle');

$I->see('amoi a Maß und n', '.inserted');
$I->see('Nach Zeile 14 einfügen:');
$I->see('An amendment comment');


$I->click('#motionListLink');
$I->click('#exportProcedureBtn');
$I->click('.linkProcedureIntern');

$I->seeElement('.motion2.moved');
$I->see('Verschoben', '.motion2');
$I->click('.motion2 .moved a');
$I->see('A2M: O’zapft is!', 'h1');
