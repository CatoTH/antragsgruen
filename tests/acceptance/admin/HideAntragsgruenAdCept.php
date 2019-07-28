<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();

$I->see('Dein Antragsgrün', '#sidebar');

$I->wantTo('disable the ad');
$I->loginAsStdAdmin();
$I->gotoStdAdminPage()->gotoConsultation();
$I->uncheckOption('#showAntragsgruenAd');
$I->submitForm('#consultationSettingsForm', [], 'save');

$I->gotoConsultationHome();
$I->dontSee('Dein Antragsgrün', '#sidebar');

$I->wantTo('enable it again');

$I->gotoStdAdminPage()->gotoConsultation();
$I->checkOption('#showAntragsgruenAd');
$I->submitForm('#consultationSettingsForm', [], 'save');

$I->gotoConsultationHome();
$I->see('Dein Antragsgrün', '#sidebar');
