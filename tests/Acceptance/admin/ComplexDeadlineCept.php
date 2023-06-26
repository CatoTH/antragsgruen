<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('set up the deadline test case');
$I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->dontSeeElement('.stickyAdminDebugFooter');
$I->dontSeeElement('.deadlineTypeComplex.motionDeadlines');
$I->seeElement('#typeDeadlineMotionsHolder');
$I->checkOption('#deadlineFormTypeComplex');
$I->seeElement('.deadlineTypeComplex.motionDeadlines');
$I->dontSeeElement('#typeDeadlineMotionsHolder');

$I->click('.motionDeadlines .deadlineAdder');
$I->click('.motionDeadlines .deadlineAdder');

$I->executeJS('$(".motionDeadlines .deadlineEntry:nth-child(1) .datetimepickerFrom input").val("01.05.2017 00:00");');
$I->executeJS('$(".motionDeadlines .deadlineEntry:nth-child(1) .datetimepickerTo input").val("15.05.2017 12:00");');
$I->executeJS('$(".motionDeadlines .deadlineEntry:nth-child(1) .phaseTitle").val("Phase 2");');
$I->executeJS('$(".motionDeadlines .deadlineEntry:nth-child(2) .datetimepickerFrom input").val("01.07.2017 00:00");');
$I->executeJS('$(".motionDeadlines .deadlineEntry:nth-child(1) .phaseTitle").val("Phase 3");');
$I->executeJS('$(".motionDeadlines .deadlineEntry:nth-child(3) .datetimepickerTo input").val("15.04.2017 12:00");');
$I->executeJS('$(".motionDeadlines .deadlineEntry:nth-child(1) .phaseTitle").val("Phase 1");');

$I->executeJS('$(".amendmentDeadlines .deadlineEntry:nth-child(1) .datetimepickerFrom input").val("01.07.2017 00:00");');
$I->executeJS('$(".amendmentDeadlines .deadlineEntry:nth-child(1) .datetimepickerTo input").val("15.07.2017 12:00");');

$I->executeJS('$(".commentDeadlines .deadlineEntry:nth-child(1) .datetimepickerTo input").val("01.08.2017 00:00");');
$I->executeJS('$(".mergingDeadlines .deadlineEntry:nth-child(1) .datetimepickerFrom input").val("15.08.2017 00:00");');

// Summary:
// - 01.04.2017: Creating motions and comments is possible [Phase 1]
// - 17.04.2017: Creating motions is NOT possible, only comments
// - 01.05.2017: Creating motions and comments is possible [Phase 2]
// - 01.06.2017: Creating motions is NOT possible, only comments
// - 01.07.2017: Creating motions, amendments and comments is possible [Phase 3]
// - 01.09.2017: Creating motions and merging is possible, no comments and amendments

$I->checkOption('#deadlineDebugMode');
$I->submitForm('.adminTypeForm', [], 'save');
$I->seeElement('.stickyAdminDebugFooter');

$I->gotoConsultationHome();
$I->fillField('#simulateAdminTimeInput', '01.04.2017 01:00');
$I->click('.stickyAdminDebugFooter .setTime');
$I->wait(1);
$I->seeElement('#sidebar .createMotion');
$I->gotoMotion();
$I->seeElement('.commentForm');
$I->seeElement('.amendmentCreate .onlyAdmins');
$I->dontSeeElement('#sidebar .mergeamendments');

$I->gotoConsultationHome();
$I->fillField('#simulateAdminTimeInput', '17.04.2017 01:00');
$I->click('.stickyAdminDebugFooter .setTime');
$I->wait(1);
$I->dontSeeElement('#sidebar .createMotion');
$I->gotoMotion();
$I->seeElement('.commentForm');
$I->seeElement('.amendmentCreate .onlyAdmins');
$I->dontSeeElement('#sidebar .mergeamendments');

$I->gotoConsultationHome();
$I->fillField('#simulateAdminTimeInput', '01.05.2017 01:00');
$I->click('.stickyAdminDebugFooter .setTime');
$I->wait(1);
$I->seeElement('#sidebar .createMotion');
$I->gotoMotion();
$I->seeElement('.commentForm');
$I->seeElement('.amendmentCreate .onlyAdmins');
$I->dontSeeElement('#sidebar .mergeamendments');

$I->gotoConsultationHome();
$I->fillField('#simulateAdminTimeInput', '01.06.2017 01:00');
$I->click('.stickyAdminDebugFooter .setTime');
$I->wait(1);
$I->dontSeeElement('#sidebar .createMotion');
$I->gotoMotion();
$I->seeElement('.commentForm');
$I->seeElement('.amendmentCreate .onlyAdmins');
$I->dontSeeElement('#sidebar .mergeamendments');

$I->gotoConsultationHome();
$I->fillField('#simulateAdminTimeInput', '01.07.2017 01:00');
$I->click('.stickyAdminDebugFooter .setTime');
$I->wait(1);
$I->seeElement('#sidebar .createMotion');
$I->gotoMotion();
$I->seeElement('.commentForm');
$I->dontSeeElement('.amendmentCreate .onlyAdmins');
$I->seeElement('.amendmentCreate');
$I->dontSeeElement('#sidebar .mergeamendments');

$I->gotoConsultationHome();
$I->fillField('#simulateAdminTimeInput', '01.09.2017 01:00');
$I->click('.stickyAdminDebugFooter .setTime');
$I->wait(1);
$I->seeElement('#sidebar .createMotion');
$I->gotoMotion();
$I->dontSeeElement('.commentForm');
$I->seeElement('.amendmentCreate .onlyAdmins');
$I->seeElement('#sidebar .mergeamendments');
