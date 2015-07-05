<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$scenario->incomplete('not implemented yet');

$I->wantTo('go to the list of all amendments-PDFs');
$I->loginAndGotoStdAdminPage();
$I->click('.amendmentPdfList');
