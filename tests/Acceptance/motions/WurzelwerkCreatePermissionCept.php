<?php

/** @var \Codeception\Scenario $scenario */
use Tests\_pages\MotionCreatePage;
use Tests\_pages\MotionEditPage;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$scenario->skip('No test available');

$I->populateDBData1();

$I->wantTo('check that I have to login in order to create a motion');
$I->gotoConsultationHome(true, '1laenderrat2015', '1laenderrat2015');
$I->seeElement('#sidebar .createMotion');
$I->click('#sidebar .createMotion');
$I->see(mb_strtoupper('Login'), 'h1');


$I->wantTo('check that I cannot create a motion as a standard user');
$I->gotoConsultationHome(true, '1laenderrat2015', '1laenderrat2015');
$I->loginAsStdUser();
$I->dontSeeElement('#sidebar .createMotion');

$I->openPage(MotionEditPage::class, [
    'subdomain'        => '1laenderrat2015',
    'consultationPath' => '1laenderrat2015',
    'motionTypeId'     => 8
]);
$I->dontSee(mb_strtoupper('Antrag stellen'), 'h1');
$I->see('Keine Berechtigung zum Anlegen von Anträgen');


$I->wantTo('check that I can create a motion as a Wurzelwerk-user');
$I->logout();
$I->gotoConsultationHome(true, '1laenderrat2015', '1laenderrat2015');
$I->loginAsGruenesNetzUser();
$I->seeElement('#sidebar .createMotion');
$I->click('#sidebar .createMotion');
$I->see(mb_strtoupper('Antrag stellen'), 'h1');


$I->wantTo('change that I can create a motion as admin');
$I->logout();
$I->gotoConsultationHome(true, '1laenderrat2015', '1laenderrat2015');
$I->loginAsStdAdmin();
$I->dontSeeElement('#sidebar .createMotion');

$I->openPage(MotionCreatePage::class, [
    'subdomain'        => '1laenderrat2015',
    'consultationPath' => '1laenderrat2015',
    'motionTypeId'     => 8
]);
$I->see(mb_strtoupper('Antrag stellen'), 'h1');
