<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('go to the list of all amendments-PDFs');
$I->loginAndGotoMotionList();
$I->click('#exportAmendmentsBtn');
$I->click('.amendmentPdfList');

$I->see('A2: O’zapft is!');
$I->see('Ä1');

$pdf = $I->downloadLink('.amendment1');
if (strlen($pdf) == 0) {
    $I->fail('File has no content');
}
