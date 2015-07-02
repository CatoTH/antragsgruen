<?php

/** @var \Codeception\Scenario $scenario */
$motionId = AcceptanceTester::FIRST_FREE_MOTION_ID;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('create a new motion');

$page = $I->gotoStdConsultationHome()->gotoMotionCreatePage();
$page->createMotion('random new motion');

$motionPage = $I->gotoMotion(true, $motionId);
$I->see(mb_strtoupper('random new motion'), 'h1');

$firstLineNo = $motionPage->getFirstLineNumber();
if ($firstLineNo != 1) {
    $I->fail('first line number is 1 - got: ' . $firstLineNo);
}


$I->wantTo('enable global line numbering');

$I->gotoStdConsultationHome();
$I->loginAsStdAdmin();
$consultationPage = $I->gotoStdAdminPage()->gotoConsultation();

$I->dontSeeCheckboxIsChecked('#lineNumberingGlobal');
$I->checkOption('#lineNumberingGlobal');
$consultationPage->saveForm();
$I->seeCheckboxIsChecked('#lineNumberingGlobal');



$I->wantTo('check if the numbering has changed');

$motionPage = $I->gotoMotion(true, $motionId);
$I->see(mb_strtoupper('random new motion'), 'h1');

$firstLineNo = $motionPage->getFirstLineNumber();
if ($firstLineNo != 71) {
    $I->fail('first line number is 71 - got: ' . $firstLineNo);
}




$I->wantTo('set an invalid title prefix');

$motionAdminPage = $I->gotoStdAdminPage()->gotoMotionIndex()->gotoMotionPage($motionId);
$I->fillField('#motionTitlePrefix', 'A2');
$I->fillField('#motionTitle', 'Another Title');
$motionAdminPage->saveForm();
$I->see('Das angegebene Antragskürzel wird bereits von einem anderen Antrag verwendet');
$I->seeInField('#motionTitlePrefix', AcceptanceTester::FIRST_FREE_MOTION_TITLE_PREFIX);
$I->seeInField('#motionTitle', 'Another Title');



$I->wantTo('set a correct title prefix');
$I->fillField('#motionTitlePrefix', 'A1');
$motionAdminPage->saveForm();
$I->seeInField('#motionTitlePrefix', 'A1');




$I->wantTo('check if the changes are reflected');
$motionPage = $I->gotoMotion(true, $motionId);
$I->see(mb_strtoupper('Another Title'), 'h1');
$firstLineNo = $motionPage->getFirstLineNumber();
if ($firstLineNo != 1) {
    $I->fail('first line number is 1 - got: ' . $firstLineNo);
}

$motionPage = $I->gotoMotion(true, 2);
$firstLineNo = $motionPage->getFirstLineNumber();
if ($firstLineNo != 2) {
    $I->fail('first line number is 2 - got: ' . $firstLineNo);
}




$I->wantTo('disable global line numbering');

$I->gotoStdConsultationHome();
$consultationPage = $I->gotoStdAdminPage()->gotoConsultation();

$I->seeCheckboxIsChecked('#lineNumberingGlobal');
$I->uncheckOption('#lineNumberingGlobal');
$consultationPage->saveForm();
$I->dontSeeCheckboxIsChecked('#lineNumberingGlobal');





$I->wantTo('check if the changes are reflected');
$motionPage = $I->gotoMotion(true, $motionId);
$firstLineNo = $motionPage->getFirstLineNumber();
if ($firstLineNo != 1) {
    $I->fail('first line number is 1 - got: ' . $firstLineNo);
}

$motionPage = $I->gotoMotion(true, 2);
$firstLineNo = $motionPage->getFirstLineNumber();
if ($firstLineNo != 1) {
    $I->fail('first line number is 1 - got: ' . $firstLineNo);
}
