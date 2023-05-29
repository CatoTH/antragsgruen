<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('check if the page can be opened at all');
$I->loginAndGotoMotionList();
$I->see('O’zapft is!');
$I->see('Textformatierungen');
$I->see('Ä2');

$I->see('Tester', '.amendment1');
$I->see('Testuser', '.amendment2');
$I->see('Testuser', '.motion2');

$I->dontSee('Ent-Freischalten', '.adminMotionTable');
$I->executeJS('$(".motion2 .actionCol .dropdown-toggle").click()');
$I->see('Ent-Freischalten', '.adminMotionTable');
$I->executeJS('$(".motion2 .actionCol .dropdown-toggle").click()');
$I->dontSee('Ent-Freischalten', '.adminMotionTable');



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
$I->seeBootboxDialog('Wirklich löschen', '.bootbox');
$I->acceptBootboxConfirm();

$I->dontSee('O’zapft is!');
$I->see('Textformatierungen');
$I->checkOption('.amendment2 input.selectbox');
$I->submitForm('.motionListForm', [], 'delete');
$I->seeBootboxDialog('Wirklich löschen', '.bootbox');
$I->acceptBootboxConfirm();

$I->dontSeeElement('.amendment2');
$I->see('Textformatierungen');
