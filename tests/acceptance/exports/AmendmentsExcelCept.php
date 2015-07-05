<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$scenario->incomplete('not implemented yet');

$I->wantTo('test the excel-export of motions from the admin interface');
$I->loginAndGotoStdAdminPage();
$I->dontSeeElement('.separated');
$I->click('.amendmentExcelOpener');
$I->seeElement('.separated');

$file = $I->downloadLink('.separated');
if (strlen($file) == 0) {
    $I->fail('File has no content');
}

$file = $I->downloadLink('.combined');
if (strlen($file) == 0) {
    $I->fail('File has no content');
}
