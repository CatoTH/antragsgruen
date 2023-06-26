<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();

$I->see('Dein Antragsgrün', '#sidebar');

$I->wantTo('disable the ad');
$I->loginAsStdAdmin();
$page = $I->gotoStdAdminPage()->gotoAppearance();
$I->uncheckOption('#showAntragsgruenAd');
$page->saveForm();

$I->gotoConsultationHome();
$I->dontSee('Dein Antragsgrün', '#sidebar');

$I->wantTo('enable it again');

$page = $I->gotoStdAdminPage()->gotoAppearance();
$I->checkOption('#showAntragsgruenAd');
$page->saveForm();

$I->gotoConsultationHome();
$I->see('Dein Antragsgrün', '#sidebar');
