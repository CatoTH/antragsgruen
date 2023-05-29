<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('Go to admin administration');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->click('#adminLink');
$I->click('#translationLink');



$I->wantTo('Change the home link');

$I->see('Start', '#homeLink');

$I->seeElement('textarea[name="string[Home]"]');
$I->fillField('textarea[name="string[Home]"]', 'Home');
$I->submitForm('#translationForm', [], 'save');

$I->dontSee('Start', '#homeLink');
$I->see('Home', '#homeLink');
$I->seeInField('textarea[name="string[Home]"]', 'Home');


$I->wantTo('Revert the change');
$I->fillField('textarea[name="string[Home]"]', '');
$I->submitForm('#translationForm', [], 'save');

$I->see('Start', '#homeLink');
$I->dontSee('Home', '#homeLink');
$I->seeInField('textarea[name="string[Home]"]', '');
