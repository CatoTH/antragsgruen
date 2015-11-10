<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('test the list of motions in ODS-Format');
$I->loginAndGotoStdAdminPage();
$file = $I->downloadLink('.motionOslides1');
if (strlen($file) == 0) {
    $I->fail('File has no content');
}
if (mb_strpos($file, 'line-through') === false) {
    $I->fail('Motion file has not all content');
}

$file = $I->downloadLink('.amendmentOslides');
if (strlen($file) == 0) {
    $I->fail('File has no content');
}
if (mb_strpos($file, 'Von Zeile 8 bis 9') === false) {
    $I->fail('Amendment file has not all content');
}
