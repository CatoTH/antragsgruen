<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('Check if only logged in users can support motions');
$I->gotoMotion();
$I->see('Du musst dich einloggen, um Anträge unterstützen zu können.');


$I->wantTo('Support this motion');
$I->loginAsStdUser();
$I->dontSee('Du musst dich einloggen, um Anträge unterstützen zu können.');
$I->submitForm('section.likes form', [], 'motionLike');
$I->see('Du unterstützt diesen Antrag nun.');
$I->see('Testuser', 'section.likes');
$I->dontSee('Abgelehnt von:', 'section.likes');
$I->see('Zustimmung von:', 'section.likes');


$I->wantTo('Withdraw my support');
$I->submitForm('section.likes form', [], 'motionSupportRevoke');
$I->see('Du stehst diesem Antrag wieder neutral gegenüber.');
$I->dontSee('Testuser', 'section.likes');
$I->dontSee('Abgelehnt von:', 'section.likes');
$I->dontSee('Zustimmung von:', 'section.likes');


$I->wantTo('Object to this motion');
$I->submitForm('section.likes form', [], 'motionDislike');
$I->see('Du widersprichst diesem Antrag nun.');
$I->see('Testuser', 'section.likes');
$I->see('Abgelehnt von:', 'section.likes');
$I->dontSee('Zustimmung von:', 'section.likes');


$I->wantTo('Withdraw my objection');
$I->submitForm('section.likes form', [], 'motionSupportRevoke');
$I->see('Du stehst diesem Antrag wieder neutral gegenüber.');
$I->dontSee('Testuser', 'section.likes');
$I->dontSee('Abgelehnt von:', 'section.likes');
$I->dontSee('Zustimmung von:', 'section.likes');
