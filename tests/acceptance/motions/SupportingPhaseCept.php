<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

function gotoSupportingMotion($I)
{
    \app\tests\_pages\MotionPage::openBy($I, [
        'subdomain'        => 'supporter',
        'consultationPath' => 'supporter',
        'motionSlug'       => 116,
    ]);
}

$I->wantTo('enable/disable liking and disliking');
gotoSupportingMotion($I);
$I->see('Dieser Antrag ist noch nicht offiziell eingereicht.');
$I->see('Du musst dich einloggen, um Anträge unterstützen zu können.');
$I->dontSeeElement('button[name=motionSupport]');
$I->dontSeeElement('section.likes');

$I->loginAsStdUser();
gotoSupportingMotion($I);
$I->seeElement('button[name=motionSupport]');
$I->dontSeeElement('button[name=motionLike]');
$I->dontSeeElement('button[name=motionDislike]');


$I->logout();
$I->loginAndGotoStdAdminPage('supporter', 'supporter')->gotoMotionTypes(10);
$I->dontSeeCheckboxIsChecked('.motionDislike');
$I->dontSeeCheckboxIsChecked('.motionLike');
$I->checkOption('.motionLike');
$I->checkOption('.motionDislike');
$I->submitForm('.adminTypeForm', [], 'save');


$I->logout();

$I->loginAsStdUser();
gotoSupportingMotion($I);
$I->seeElement('section.likes');
$I->seeElement('button[name=motionLike]');
$I->seeElement('button[name=motionDislike]');
$I->seeElement('button[name=motionSupport]');


$I->wantTo('support this motion');

$I->submitForm('.motionSupportForm', [], 'motionSupport');
$I->see('Du unterstützt diesen Antrag nun.');
$I->dontSeeElement('button[name=motionSupport]');
$I->see('Du!', 'section.supporters');
$I->see('Testuser', 'section.supporters');
$I->see('Die Mindestzahl an Unterstützer*innen (1) wurde erreicht');
$I->seeElement('button[name=motionSupportRevoke]');


$I->wantTo('revoke the support');

$I->submitForm('.motionSupportForm', [], 'motionSupportRevoke');
$I->see('Du stehst diesem Antrag wieder neutral gegenüber');
$I->see('aktueller Stand: 0');


$I->wantTo('support it again');

$I->submitForm('.motionSupportForm', [], 'motionSupport');
$I->see('Du unterstützt diesen Antrag nun.');


$I->logout();


$I->wantTo('submit the motion');

$I->loginAsStdAdmin();
gotoSupportingMotion($I);
$I->see('Testuser', 'section.supporters');
$I->submitForm('.motionSupportFinishForm', [], 'motionSupportFinish');
$I->see('Der Antrag ist nun offiziell eingereicht');
$I->see('Eingereicht (ungeprüft)', '.motionData');
