<?php

use Tests\_pages\ManagerStartPage;
use Tests\Support\AcceptanceTester;

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('ensure that ManagerStartPage works');
$I->openPage(ManagerStartPage::class);
$I->see(mb_strtoupper('Antragsgrün - das grüne Antragstool'), 'h1');


$I->wantTo('go to the legal page');
$I->click('#legalLink');
$I->see('Impressum', 'h1');

$I->dontSeeElement('.editCaller');



$I->wantTo('edit the page');

$I->loginAsGlobalAdmin();

$I->seeElement('.editCaller');

$I->wantTo('Edit the content');
$I->executeJS('$(".contentPage").find(".editCaller").click();');
$I->wait(2);
$I->seeElement('.contentPage .textSaver button');
$I->executeJS('CKEDITOR.instances.stdTextHolder.setData("<b>Bold test</b>");');
$I->executeJS('$(".contentPage").find(".textSaver button").click();');
$I->wait(1);

$I->dontSeeElement('.contentPage .textSaver button');
$I->see('Bold test');

$I->click('#legalLink');

$I->see('Bold test');


$I->wantTo('check the privacy statement is visible');

$I->click('#privacyLink');
$I->see('Datenschutz', 'h1');
