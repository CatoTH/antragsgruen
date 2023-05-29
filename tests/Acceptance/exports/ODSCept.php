<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('test the list of motions in ODS-Format');
$I->loginAndGotoMotionList();
$I->click('#exportMotionBtn1');
$I->seeElement('.motionODS1');
$file = $I->downloadLink('.motionODS1');
if (strlen($file) == 0) {
    $I->fail('File has no content');
}

$I->click('#exportAmendmentsBtn');
$I->seeElement('.amendmentOds');
$file = $I->downloadLink('.amendmentOds');
if (strlen($file) == 0) {
    $I->fail('File has no content');
}
