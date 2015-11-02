<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('create a draft motion logged out');
$createPage = $I->gotoConsultationHome()->gotoMotionCreatePage();
$createPage->fillInValidSampleData('Testantrag 1');
$createPage->saveForm();
$I->see(mb_strtoupper('Antrag bestätigen'), 'h1');
$I->gotoConsultationHome();

$I->wantTo('edit the draft');
\app\tests\_pages\MotionEditPage::openBy($I, [
    'subdomain'        => 'stdparteitag',
    'consultationPath' => 'std-parteitag',
    'motionId'         => AcceptanceTester::FIRST_FREE_MOTION_ID,
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
\app\tests\_pages\MotionEditPage::openBy($I, [
    'subdomain'        => 'stdparteitag',
    'consultationPath' => 'std-parteitag',
    'motionId'         => AcceptanceTester::FIRST_FREE_MOTION_ID + 1,
]);
$I->see('Antrag stellen', 'h1');
$I->seeInField(['name' => 'sections[1]'], 'Testantrag 2');


$I->wantTo('edit the draft logged out (should not work)');
$I->logout();
\app\tests\_pages\MotionEditPage::openBy($I, [
    'subdomain'        => 'stdparteitag',
    'consultationPath' => 'std-parteitag',
    'motionId'         => AcceptanceTester::FIRST_FREE_MOTION_ID + 1,
]);
$I->dontSee('Antrag stellen', 'h1');
$I->dontSeeElement(['name' => 'sections[1]']);
