<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoStdConsultationHome();
$I->click('#legalLink');
$I->see('Impressum', 'h1');

$I->click('#privacyLink');
$I->see('Datenschutz', 'h1');
$I->see('§ 55 Abs. 2 RStV');

// @TODO Same for the main site without a consultation
