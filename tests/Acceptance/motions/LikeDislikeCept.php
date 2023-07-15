<?php

/** @var \Codeception\Scenario $scenario */
use app\models\policies\IPolicy;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('verify that supporting motions is disabled by default');
$I->gotoMotion(true, 3);
$I->dontSeeElement('section.likes');



$I->wantTo('enable supporting motions for logged in users');

$I->loginAsStdAdmin();
$mtPage = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->selectOption('#typePolicySupportMotions', IPolicy::POLICY_LOGGED_IN);
$I->checkOption('.motionLike');
$I->checkOption('.motionDislike');
$mtPage->saveForm();

$I->gotoConsultationHome();
$I->logout();



$I->wantTo('check if only logged in users can support motions');
$I->gotoMotion(true, 3);
$I->see('Du musst dich einloggen, um Anträge unterstützen zu können.');


$I->wantTo('support this motion');
$I->loginAsStdUser();
$I->dontSee('Du musst dich einloggen, um Anträge unterstützen zu können.');
$I->submitForm('section.likes form', [], 'motionLike');
$I->see('Du stimmst diesem Antrag nun zu.');
$I->see('Testuser', 'section.likes');
$I->see('Du!', 'section.likes');
$I->dontSee('Ablehnung:', 'section.likes');
$I->see('Zustimmung:', 'section.likes');

$I->wantTo('watch this page logged out');
$I->logout();
$I->see('Testuser', 'section.likes');
$I->dontSee('Du!', 'section.likes');


$I->wantTo('withdraw my support');
$I->loginAsStdUser();
$I->submitForm('section.likes form', [], 'motionSupportRevoke');
$I->see('Du stehst diesem Antrag wieder neutral gegenüber.');
$I->dontSee('Testuser', 'section.likes');
$I->dontSee('Ablehnung:', 'section.likes');
$I->dontSee('Zustimmung:', 'section.likes');


$I->wantTo('object to this motion');
$I->submitForm('section.likes form', [], 'motionDislike');
$I->see('Du lehnst diesen Antrag nun ab.');
$I->see('Testuser', 'section.likes');
$I->see('Du!', 'section.likes');
$I->see('Ablehnung:', 'section.likes');
$I->dontSee('Zustimmung:', 'section.likes');


$I->wantTo('withdraw my objection');
$I->submitForm('section.likes form', [], 'motionSupportRevoke');
$I->see('Du stehst diesem Antrag wieder neutral gegenüber.');
$I->dontSee('Testuser', 'section.likes');
$I->dontSee('Ablehnung:', 'section.likes');
$I->dontSee('Zustimmung:', 'section.likes');
