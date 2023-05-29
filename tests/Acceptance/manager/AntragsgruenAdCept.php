<?php

/** @var \Codeception\Scenario $scenario */
use app\models\db\Site;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('see the ad');
$I->gotoConsultationHome();
$I->see(mb_strtoupper('Dein Antragsgrün'), '#sidebar');


$I->wantTo('deactivate the ad');
/** @var Site $site */
$site                         = Site::findOne(1);
$settings                     = $site->getSettings();
$settings->showAntragsgruenAd = false;
$site->setSettings($settings);
$site->save();

$I->gotoConsultationHome();
$I->dontSee(mb_strtoupper('Dein Antragsgrün'), '#sidebar');


$I->wantTo('activate the ad again');
/** @var Site $site */
$site                         = Site::findOne(1);
$settings                     = $site->getSettings();
$settings->showAntragsgruenAd = true;
$site->setSettings($settings);
$site->save();

$I->gotoConsultationHome();
$I->see(mb_strtoupper('Dein Antragsgrün'), '#sidebar');
