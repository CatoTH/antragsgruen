<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('prepare the test case');

$I->loginAndGotoStdAdminPage();
$I->click('.siteConsultationsLink');
$I->fillField('#newTitle', 'Test3');
$I->fillField('#newShort', 'test3');
$I->fillField('#newPath', 'test3');
$I->submitForm('.consultationCreateForm', [], 'createConsultation');


$I->wantTo('move the motion to test3');

$I->gotoMotion(true, 'Testing_proposed_changes-630');
$I->dontSeeElement('#section_4_0');
$I->click('#sidebar .adminEdit a');
$I->click('.sidebarActions .move');

$I->dontSeeElement('.moveToConsultationItem');
$I->checkOption("//input[@name='operation'][@value='move']");
$I->seeCheckboxIsChecked("//input[@name='operation'][@value='move']");
$I->checkOption("//input[@name='target'][@value='consultation']");
$I->seeElement('.moveToConsultationItem');

$I->submitForm('.adminMoveForm', [], 'move');
$I->click('.alert-success a');
$I->see('Test3', '.breadcrumb');
$I->gotoConsultationHome(true, 'stdparteitag', 'test3');
$I->seeElement('.motionRow118');

$I->wantTo('make sure the merging still works');

$I->click('.motionLink118');
$I->click('#sidebar .mergeamendments a');
$I->wait(0.2);
$I->executeJS('$(".toMergeAmendments .selectAll").trigger("click");');
$I->submitForm('.mergeAllRow', [], null);
$I->wait(0.5);
$sectionId = AcceptanceTester::FIRST_FREE_MOTION_SECTION + 1;
$I->see('A big replacement', '#paragraphWrapper_' . $sectionId . '_1 .collidingParagraph');


$I->wantTo('make a pure copy of a motion');

$I->gotoMotion(); // A2
$I->click('#sidebar .adminEdit a');
$I->click('.sidebarActions .move');

$I->dontSeeElement('.moveToConsultationItem');
$I->checkOption("//input[@name='operation'][@value='copynoref']");
$I->seeCheckboxIsChecked("//input[@name='operation'][@value='copynoref']");
$I->checkOption("//input[@name='target'][@value='consultation']");
$I->seeElement('.moveToConsultationItem');

$I->submitForm('.adminMoveForm', [], 'move');
$I->click('.alert-success a');
$I->see('Test3', '.breadcrumb');
$I->gotoConsultationHome(true, 'stdparteitag', 'test3');
$I->seeElement('.motionRow' . AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->click('.motionRow' . AcceptanceTester::FIRST_FREE_MOTION_ID . ' .motionLink' . AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->see('Bavaria ipsum dolor sit amet');

$I->wantTo('Make sure A2 is still at its old place');
$I->gotoConsultationHome();
$I->seeElement('.motionRow2');
$I->dontSeeElement('.motionRow2.moved');
$I->click('.motionRow2 .motionLink2');
$I->see('Bavaria ipsum dolor sit amet');

$I->wantTo('copy the motion back to test2');

$I->gotoConsultationHome(true, 'stdparteitag', 'test3');
$I->click('.motionLink118');
$I->click('#sidebar .adminEdit a');
$I->click('.sidebarActions .move');

$I->dontSeeElement('.moveToConsultationItem');
$I->checkOption("//input[@name='operation'][@value='copy']");
$I->seeCheckboxIsChecked("//input[@name='operation'][@value='copy']");
$I->checkOption("//input[@name='target'][@value='consultation']");
$I->seeElement('.moveToConsultationItem');
$I->fillField('#motionTitlePrefix', 'A8.1');

$I->submitForm('.adminMoveForm', [], 'move');

$I->gotoConsultationHome(true, 'stdparteitag', 'test3');

$I->seeElement('.motionRow118.moved');
$I->click('.motionLink118');
$I->click('.motionReplacedBy a');
$I->see('A8.1: Testing proposed changes');

$I->click('#sidebar .mergeamendments a');
$I->wait(0.2);
$I->executeJS('$(".toMergeAmendments .selectAll").trigger("click");');
$I->submitForm('.mergeAllRow', [], null);
$I->wait(0.5);
$I->see('A big replacement', '#paragraphWrapper_2_1 .collidingParagraph');
