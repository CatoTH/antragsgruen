<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('verify that supporting motion is disabled by default');
$I->gotoMotion();
$I->dontSeeElement('section.likes');



$I->wantTo('enable supporting motions for logged in users');

$I->loginAsStdAdmin();
$mtPage = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->selectOption(['name' => 'type[policySupport]'], \app\models\policies\IPolicy::POLICY_LOGGED_IN);
$mtPage->saveForm();

$I->gotoStdConsultationHome();
$I->logout();



$I->wantTo('check if only logged in users can support motions');
$I->gotoMotion();
$I->see('Du musst dich einloggen, um Anträge unterstützen zu können.');


$I->wantTo('support this motion');
$I->loginAsStdUser();
$I->dontSee('Du musst dich einloggen, um Anträge unterstützen zu können.');
$I->submitForm('section.likes form', [], 'motionLike');
$I->see('Du unterstützt diesen Antrag nun.');
$I->see('Testuser', 'section.likes');
$I->dontSee('Abgelehnt von:', 'section.likes');
$I->see('Zustimmung von:', 'section.likes');


$I->wantTo('withdraw my support');
$I->submitForm('section.likes form', [], 'motionSupportRevoke');
$I->see('Du stehst diesem Antrag wieder neutral gegenüber.');
$I->dontSee('Testuser', 'section.likes');
$I->dontSee('Abgelehnt von:', 'section.likes');
$I->dontSee('Zustimmung von:', 'section.likes');


$I->wantTo('object to this motion');
$I->submitForm('section.likes form', [], 'motionDislike');
$I->see('Du widersprichst diesem Antrag nun.');
$I->see('Testuser', 'section.likes');
$I->see('Abgelehnt von:', 'section.likes');
$I->dontSee('Zustimmung von:', 'section.likes');


$I->wantTo('withdraw my objection');
$I->submitForm('section.likes form', [], 'motionSupportRevoke');
$I->see('Du stehst diesem Antrag wieder neutral gegenüber.');
$I->dontSee('Testuser', 'section.likes');
$I->dontSee('Abgelehnt von:', 'section.likes');
$I->dontSee('Zustimmung von:', 'section.likes');
