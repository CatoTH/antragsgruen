<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('enable amendments to amendments and set up complex deadlines');
$I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->checkOption('#allowAmendmentsToAmendments');

$I->checkOption('#deadlineFormTypeComplex');
$I->seeElement('.deadlineTypeComplex.amendmentDeadlines');
$I->seeElement('.deadlineTypeComplex.amendmentsToAmendmentsDeadlines');

$I->clickJS('.amendmentDeadlines .deadlineAdder');
$I->executeJS('$(".amendmentDeadlines .deadlineEntry:nth-child(1) .datetimepickerFrom input").val("01.07.2017 00:00");');
$I->executeJS('$(".amendmentDeadlines .deadlineEntry:nth-child(1) .datetimepickerTo input").val("01.08.2017 00:00");');

$I->checkOption('#deadlineDebugMode');
$I->submitForm('.adminTypeForm', [], 'save');
$I->seeElement('.stickyAdminDebugFooter');


$I->wantTo('verify that amendments to amendments fall back to the amendments deadline when not configured separately');

$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->fillField('#simulateAdminTimeInput', '15.07.2017 01:00');
$I->click('.stickyAdminDebugFooter .setTime');
$I->wait(1);
$I->seeElement('#sidebar .amendmentCreate');
$I->dontSeeElement('#sidebar .amendmentCreate .onlyAdmins');

$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->fillField('#simulateAdminTimeInput', '15.09.2017 01:00');
$I->click('.stickyAdminDebugFooter .setTime');
$I->wait(1);
$I->seeElement('#sidebar .amendmentCreate .onlyAdmins');


$I->wantTo('configure a separate, non-overlapping deadline for amendments to amendments');
$I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->clickJS('.amendmentsToAmendmentsDeadlines .deadlineAdder');
$I->executeJS('$(".amendmentsToAmendmentsDeadlines .deadlineEntry:nth-child(1) .datetimepickerFrom input").val("01.09.2017 00:00");');
$I->executeJS('$(".amendmentsToAmendmentsDeadlines .deadlineEntry:nth-child(1) .datetimepickerTo input").val("01.10.2017 00:00");');
$I->clickJS(".adminTypeForm button[name='save']");
$I->wait(0.5);


$I->wantTo('verify that amendments to amendments now follow their own deadline, independent of the amendments deadline');

// Inside the (still unchanged) amendments deadline, but outside the newly configured
// amendments-to-amendments deadline: creating a sub-amendment should now require admin rights.
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->fillField('#simulateAdminTimeInput', '15.07.2017 01:00');
$I->click('.stickyAdminDebugFooter .setTime');
$I->wait(1);
$I->seeElement('#sidebar .amendmentCreate .onlyAdmins');

// Outside the amendments deadline, but inside the amendments-to-amendments deadline: creating
// a sub-amendment should be open to everyone, even though plain amendments are closed by now.
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->fillField('#simulateAdminTimeInput', '15.09.2017 01:00');
$I->click('.stickyAdminDebugFooter .setTime');
$I->wait(1);
$I->seeElement('#sidebar .amendmentCreate');
$I->dontSeeElement('#sidebar .amendmentCreate .onlyAdmins');

// Outside both deadlines: blocked for regular users either way.
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->fillField('#simulateAdminTimeInput', '15.11.2017 01:00');
$I->click('.stickyAdminDebugFooter .setTime');
$I->wait(1);
$I->seeElement('#sidebar .amendmentCreate .onlyAdmins');
