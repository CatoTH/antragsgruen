<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$scenario->incomplete('Not finished yet');

$I->wantTo('test the list of motions in ODS-Format');
$I->loginAndGotoStdAdminPage();
$file = $I->downloadLink('.motionODS1');
if (strlen($file) == 0) {
    $I->fail('File has no content');
}
