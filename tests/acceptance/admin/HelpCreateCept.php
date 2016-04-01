<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('not see the help page');
$I->gotoConsultationHome();
$I->dontSeeElement('#helpLink');

$I->loginAsStdAdmin();
$I->dontSeeElement('#helpLink');


$I->wantTo('create the help page');
$I->gotoStdAdminPage();
$I->click('#helpCreateLink');
$I->click('.editCaller');
$I->wait(1);
$I->executeJS('CKEDITOR.instances.stdTextHolder.setData("<p>New text</p>");');
$I->click('.submitBtn');

$I->wantTo('not see the help page');
$I->gotoConsultationHome();
$I->seeElement('#helpLink');
$I->logout();
$I->seeElement('#helpLink');
$I->click('#helpLink');
$I->see('New text');
