<?php

/** @var \Codeception\Scenario $scenario */

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$page = $I->loginAsStdAdmin()->gotoStdAdminPage()->gotoAppearance();
$I->selectOption('#startLayoutType', '5');
$page->saveForm();

$I->gotoConsultationHome();
$I->seeElement('.motionRow2 .amendmentsToggler.closed');
$I->dontSeeElement('.amendmentRow1');

$I->executeJS('$(".motionRow2 .amendmentsToggler button").click();');

$I->seeElement('.amendmentRow1');
