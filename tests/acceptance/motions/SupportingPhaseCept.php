<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$motionUrl = \app\tests\_pages\MotionPage::getPageUrl($I, [
    'subdomain'        => 'supporter',
    'consultationPath' => 'supporter',
    'motionSlug'       => 116,
]);


$I->wantTo('check the admin settings');
$I->loginAndGotoStdAdminPage('supporter', 'supporter')->gotoMotionTypes(10);
$I->seeInField('#typeMinSupporters', 1);
$I->selectOption('#typeSupportType', \app\models\supportTypes\ISupportType::ONLY_INITIATOR);
$I->dontSeeElement('#typeMinSupporters');
$I->selectOption('#typeSupportType', \app\models\supportTypes\ISupportType::COLLECTING_SUPPORTERS);
$I->seeElement('#typeMinSupporters');

$I->logout();



$I->wantTo('enable/disable liking and disliking');
$I->amOnPage($motionUrl);
$I->see('Dieser Antrag ist noch nicht eingereicht.');
$I->see('Du musst dich einloggen, um Anträge unterstützen zu können.');
$I->dontSeeElement('button[name=motionSupport]');
$I->dontSeeElement('section.likes');

$I->loginAsStdUser();
$I->amOnPage($motionUrl);
$I->seeElement('button[name=motionSupport]');
$I->dontSeeElement('button[name=motionLike]');
$I->dontSeeElement('button[name=motionDislike]');


$I->logout();
$I->loginAndGotoStdAdminPage('supporter', 'supporter')->gotoMotionTypes(10);
$I->dontSeeCheckboxIsChecked('.motionDislike');
$I->dontSeeCheckboxIsChecked('.motionLike');
$I->checkOption('.motionLike');
$I->checkOption('.motionDislike');
$I->checkOption('#typeHasOrgaRow input[type=checkbox]');
$I->submitForm('.adminTypeForm', [], 'save');


$I->logout();

$I->loginAsStdUser();
$I->amOnPage($motionUrl);
$I->seeElement('section.likes');
$I->seeElement('button[name=motionLike]');
$I->seeElement('button[name=motionDislike]');
$I->seeElement('button[name=motionSupport]');


$I->wantTo('support this motion');

$I->fillField('input[name=motionSupportName]', 'My name');
$I->fillField('input[name=motionSupportOrga]', 'My organisation');
$I->submitForm('.motionSupportForm', [], 'motionSupport');
$I->see('Du unterstützt diesen Antrag nun.');
$I->dontSeeElement('button[name=motionSupport]');
$I->see('Du!', 'section.supporters');
$I->dontSee('Testuser', 'section.supporters');
$I->see('My name', 'section.supporters');
$I->see('My organisation', 'section.supporters');
$I->see('Die Mindestzahl an Unterstützer*innen (1) wurde erreicht');
$I->seeElement('button[name=motionSupportRevoke]');


$I->wantTo('revoke the support');

$I->submitForm('.motionSupportForm', [], 'motionSupportRevoke');
$I->see('Du stehst diesem Antrag wieder neutral gegenüber');
$I->see('aktueller Stand: 0');


$I->wantTo('support it again');

$I->executeJS('$("input[name=motionSupportOrga]").removeAttr("required");');
$I->submitForm('.motionSupportForm', [], 'motionSupport');
$I->dontSee('Du unterstützt diesen Antrag nun.');
$I->see('No organization entered');

$I->fillField('input[name=motionSupportOrga]', 'My organisation');
$I->submitForm('.motionSupportForm', [], 'motionSupport');
$I->see('Du unterstützt diesen Antrag nun.');

$I->logout();


$I->wantTo('submit the motion');

$I->loginAsStdAdmin();
$I->amOnPage($motionUrl);
$I->see('Testuser', 'section.supporters');
$I->submitForm('.motionSupportFinishForm', [], 'motionSupportFinish');
$I->see('Der Antrag ist nun offiziell eingereicht');
$I->see('Eingereicht (ungeprüft)', '.motionData');


$I->logout();


$I->wantTo('ensure I can\'t revoke my support once the motion has been submitted');
$I->loginAsStdUser();
$I->amOnPage($motionUrl);
$I->see('Du!', 'section.supporters');
$I->dontSeeElement('button[name=motionSupportRevoke]');




$I->wantTo('check that motions created as normal person are in supporting phase');

$I->gotoConsultationHome(false, 'supporter', 'supporter')->gotoMotionCreatePage(10, true, 'supporter', 'supporter');
$I->fillField('#sections_30', 'Title as normal person');
$I->executeJS('CKEDITOR.instances.sections_31_wysiwyg.setData("<p><strong>Test</strong></p>");');
$I->submitForm('#motionEditForm', [], 'save');
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->see('benötigt dieser mindestens 1 Unterstützer*innen.');


$I->wantTo('check that motions created as organizations are not in supporting phase');

$I->gotoConsultationHome(false, 'supporter', 'supporter')->gotoMotionCreatePage(10, true, 'supporter', 'supporter');
$I->fillField('#sections_30', 'Title as organization');
$I->executeJS('CKEDITOR.instances.sections_31_wysiwyg.setData("<p><strong>Test</strong></p>");');
$I->checkOption('#personTypeOrga');
$I->fillField('#initiatorPrimaryName', 'My organization');
$I->fillField('#resolutionDate', '01.01.2016');
$I->submitForm('#motionEditForm', [], 'save');
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->see('Du hast den Antrag eingereicht. Er wird nun auf formale Richtigkeit geprüft und dann freigeschaltet.');


$I->gotoConsultationHome(false, 'supporter', 'supporter');
$I->see('Eingereicht (ungeprüft)', '.myMotionList');
$I->see('Unterstützer*innen sammeln', '.myMotionList');
