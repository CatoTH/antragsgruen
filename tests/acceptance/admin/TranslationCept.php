<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('Go to admin administration');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->click('#adminLink');
$I->click('#translationLink');



$I->wantTo('Change the help link');

$I->see('Hilfe', '#helpLink');

$I->seeElement('textarea[placeholder=Hilfe]');
$I->fillField('textarea[placeholder=Hilfe]', 'HelpMe');
$I->submitForm('#translationForm', [], 'save');

$I->dontSee('Hilfe', '#helpLink');
$I->see('HelpMe', '#helpLink');
$I->seeInField('textarea[placeholder=Hilfe]', 'HelpMe');


$I->wantTo('Revert the change');
$I->fillField('textarea[placeholder=Hilfe]', '');
$I->submitForm('#translationForm', [], 'save');

$I->see('Hilfe', '#helpLink');
$I->dontSee('HelpMe', '#helpLink');
$I->seeInField('textarea[placeholder=Hilfe]', '');
