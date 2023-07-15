<?php

/** @var \Codeception\Scenario $scenario */
use app\models\db\Motion;
use Tests\_pages\MotionEditPage;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('create a draft motion logged out');
$createPage = $I->gotoConsultationHome()->gotoMotionCreatePage();
$createPage->fillInValidSampleData('Testantrag 1');
$createPage->saveForm();
$I->see(mb_strtoupper('Antrag bestätigen'), 'h1');
$I->gotoConsultationHome();

$I->wantTo('edit the draft');
/** @var Motion $motion */
$motion = Motion::findOne(AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->openPage(MotionEditPage::class, [
    'subdomain'        => 'stdparteitag',
    'consultationPath' => 'std-parteitag',
    'motionSlug'       => $motion->getMotionSlug(),
]);
$I->see('Antrag stellen', 'h1');
$I->seeInField(['name' => 'sections[1]'], 'Testantrag 1');


$I->wantTo('create a draft motion logged in');
$createPage = $I->gotoConsultationHome()->gotoMotionCreatePage();
$I->loginAsStdUser();
$createPage->fillInValidSampleData('Testantrag 2');
$createPage->saveForm();
$I->see(mb_strtoupper('Antrag bestätigen'), 'h1');
$I->gotoConsultationHome();

$I->wantTo('edit the draft');
/** @var Motion $motion */
$motion = Motion::findOne(AcceptanceTester::FIRST_FREE_MOTION_ID + 1);
$I->openPage(MotionEditPage::class, [
    'subdomain'        => 'stdparteitag',
    'consultationPath' => 'std-parteitag',
    'motionSlug'         => $motion->getMotionSlug(),
]);
$I->see('Antrag stellen', 'h1');
$I->seeInField(['name' => 'sections[1]'], 'Testantrag 2');


$I->wantTo('edit the draft logged out (should not work)');
$I->logout();
/** @var Motion $motion */
$motion = Motion::findOne(AcceptanceTester::FIRST_FREE_MOTION_ID + 1);
$I->openPage(MotionEditPage::class, [
    'subdomain'        => 'stdparteitag',
    'consultationPath' => 'std-parteitag',
    'motionSlug'         => $motion->getMotionSlug(),
]);
$I->dontSee('Antrag stellen', 'h1');
$I->dontSeeElement(['name' => 'sections[1]']);
