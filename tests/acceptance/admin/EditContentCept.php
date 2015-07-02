<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('Login as regular user');
$I->gotoStdConsultationHome();
$I->loginAsStdUser();
$I->dontSee('ADMIN', '#adminLink');
$I->dontSee('Bearbeiten', '.editCaller');

$I->wantTo('Login as admin');
$I->logout();
$I->loginAsStdAdmin();
$I->see('ADMIN', '#adminLink');
$I->see('Bearbeiten', '.editCaller');
$I->see('Hallo auf Antragsgrün');

$I->wantTo('Edit the content');
$I->executeJS('$(".contentPageWelcome").find(".editCaller").click();');
$I->wait(2);
$I->executeJS('CKEDITOR.instances.stdTextHolder.setData("<b>Bold test</b>");');
$I->executeJS('$(".contentPageWelcome").find(".textSaver button").click();');
$I->see('Bold test');

$I->gotoStdConsultationHome();
$I->dontSee('Hallo auf Antragsgrün');
$I->see('Bold test');

$I->wantTo('Go to the help page');
$I->click('#helpLink');
$I->see('ADMIN', '#adminLink');
$I->see('Bearbeiten', '.editCaller');
$I->see('HILFE', 'h1');

$I->wantTo('Edit the content');
$I->executeJS('$(".contentPage").find(".editCaller").click();');
$I->wait(2);
$I->executeJS('CKEDITOR.instances.stdTextHolder.setData("<b>Some arbitrary text</b>");');
$I->executeJS('$(".contentPage").find(".textSaver button").click();');
$I->see('Some arbitrary text');

$I->reloadPage();
$I->see('Some arbitrary text');

$I->wantTo('See the page as a normal user now');
$I->logout();
$I->see('Some arbitrary text');
$I->dontSee('Bearbeiten', '.editCaller');

$I->validateHTML();
