<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('check if the page can be opened at all');
$page = $I->loginAndGotoStdAdminPage()->gotoMotionList();
$I->see('O’zapft is!');
$I->see('Textformatierungen');
$I->see('Ä2');

$I->dontSee('Freischalten zurücknehmen');
$I->executeJS('$(".motion2 .actionCol .dropdown-toggle").click()');
$I->see('Freischalten zurücknehmen');
$I->executeJS('$(".motion2 .actionCol .dropdown-toggle").click()');
$I->dontSee('Freischalten zurücknehmen');



$I->wantTo('test screening and undoing it');
$I->dontSee('ungeprüft');
$I->checkOption('.motion3 input.selectbox');
$I->checkOption('.amendment1 input.selectbox');
$I->submitForm('.motionListForm', [], 'unscreen');
$I->see('ungeprüft', '.motion3');
$I->see('ungeprüft', '.amendment1');
$I->checkOption('.motion3 input.selectbox');
$I->checkOption('.amendment1 input.selectbox');
$I->submitForm('.motionListForm', [], 'screen');
$I->dontSee('ungeprüft');


$I->wantTo('test deleting motions and amendments');
$I->see('O’zapft is!');
$I->checkOption('.motion2 input.selectbox');
$I->submitForm('.motionListForm', [], 'delete');
$I->dontSee('O’zapft is!');

$I->see('Textformatierungen');
$I->checkOption('.amendment2 input.selectbox');
$I->submitForm('.motionListForm', [], 'delete');
$I->dontSeeElement('.amendment2');
$I->see('Textformatierungen');
