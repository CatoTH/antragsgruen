<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->loginAsStdUser();

$I->click('#myAccountLink');
$I->see('Einstellungen', '.breadcrumb');

$I->checkOption('input[name=accountDeleteConfirm]');
$I->submitForm('.accountDeleteForm', [], 'accountDelete');
$I->dontSee('Einstellungen', '.breadcrumb');

$I->gotoConsultationHome();
$I->loginAsStdUser();

$I->see('Benutzer*innenname nicht gefunden');
