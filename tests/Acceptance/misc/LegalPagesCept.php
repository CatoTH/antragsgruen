<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->click('#legalLink');
$I->see('Impressum', 'h1');

$I->click('#privacyLink');
$I->see(mb_strtoupper('Datenschutzerklärung'), 'h1');
$I->see('None of your data are belong to us');
