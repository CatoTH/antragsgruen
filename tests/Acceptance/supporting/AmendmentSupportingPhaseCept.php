<?php

/** @var \Codeception\Scenario $scenario */
use app\models\db\IMotion;
use app\models\supportTypes\SupportBase;
use Tests\_pages\AmendmentPage;
use Tests\_pages\MotionPage;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$amendmentUrl = AmendmentPage::getPageUrl($I, [
    'subdomain'        => 'supporter',
    'consultationPath' => 'supporter',
    'motionSlug'       => 116,
    'amendmentId'      => AcceptanceTester::FIRST_FREE_AMENDMENT_ID,
]);

$I->gotoConsultationHome(false, 'supporter', 'supporter');
$I->dontSeeElementInDOM('#sidebar .collecting');


$I->wantTo('publish the amendment');
$I->openPage(MotionPage::class, [
    'subdomain'        => 'supporter',
    'consultationPath' => 'supporter',
    'motionSlug'       => 116,
]);
$I->dontSeeElement("#sidebar .amendmentCreate a");
$I->loginAsStdAdmin();
$I->seeElement("#sidebar .amendmentCreate a");
$I->click('#sidebar .adminEdit a');
$I->selectOption('#motionStatus', IMotion::STATUS_SUBMITTED_SCREENED);
$I->submitForm('#motionUpdateForm', [], 'save');

$I->wantTo('activate the collecting page');
$page = $I->gotoStdAdminPage('supporter', 'supporter')->gotoAppearance();
$I->checkOption('#collectingPage');
$page->saveForm();

$I->logout();


$I->wantTo('check that amendments created as normal person are in supporting phase');

$I->gotoConsultationHome(false, 'supporter', 'supporter');
$I->loginAsStdUser();
$I->openPage(MotionPage::class, [
    'subdomain'        => 'supporter',
    'consultationPath' => 'supporter',
    'motionSlug'       => 116,
]);
$I->click("#sidebar .amendmentCreate a");
$I->wait(1);
$I->fillField('#sections_30', 'New title');
$I->wait(0.2);
$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');

$I->see('benötigt dieser mindestens 1 Unterstützer*innen.');

$I->gotoConsultationHome(false, 'supporter', 'supporter');
$I->see('Unterstützer*innen sammeln', '.myAmendmentList');
$I->dontSee('Eingereicht (ungeprüft)', '.myAmendmentList');

$I->click('#sidebar .collecting a');

$I->see('ÄA von Testuser, ab Zeile 1', '.motionList');
$I->see('Aktueller Stand: 0 / 1', '.amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);


$I->wantTo('check that amendments created as organizations are not in supporting phase');

$I->openPage(MotionPage::class, [
    'subdomain'        => 'supporter',
    'consultationPath' => 'supporter',
    'motionSlug'       => 116,
]);
$I->click("#sidebar .amendmentCreate a");
$I->wait(1);
$I->fillField('#sections_30', 'Title as organization');
$I->checkOption('#personTypeOrga');
$I->fillField('#initiatorPrimaryName', 'My orga name');
$I->fillField('#resolutionDate', '01.01.2016');
$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');
$I->see('Du hast den Änderungsantrag eingereicht. Er wird nun auf formale Richtigkeit geprüft und dann freigeschaltet.');


$I->gotoConsultationHome(false, 'supporter', 'supporter');
$I->see('Eingereicht (ungeprüft)', '.myAmendmentList');

$I->logout();


$I->wantTo('check the admin settings');
$I->loginAndGotoStdAdminPage('supporter', 'supporter')->gotoMotionTypes(10);
$I->seeInField('#typeMinSupporters', '1');
$I->selectOption('#typeSupportType', SupportBase::ONLY_INITIATOR);
$I->dontSeeElement('#typeMinSupporters');
$I->selectOption('#typeSupportType', SupportBase::COLLECTING_SUPPORTERS);
$I->seeElement('#typeMinSupporters');

$I->submitForm('#policyFixForm', [], 'supportCollPolicyFix');


$I->logout();


$I->wantTo('enable/disable liking and disliking');
$I->amOnPage($amendmentUrl);
$I->see('Dieser Änderungsantrag ist noch nicht eingereicht.');
$I->see('Du musst dich einloggen, um Anträge unterstützen zu können.');
$I->dontSeeElement('button[name=motionSupport]');
$I->dontSeeElement('section.likes');

$I->loginAsStdAdmin();
$I->amOnPage($amendmentUrl);
$I->seeElement('button[name=motionSupport]');
$I->dontSeeElement('button[name=motionLike]');
$I->dontSeeElement('button[name=motionDislike]');


$I->gotoStdAdminPage('supporter', 'supporter')->gotoMotionTypes(10);
$I->dontSeeCheckboxIsChecked('.amendmentDislike');
$I->dontSeeCheckboxIsChecked('.amendmentLike');
$I->checkOption('.amendmentLike');
$I->checkOption('.amendmentDislike');
$I->checkOption('#typeHasOrga');
$I->submitForm('.adminTypeForm', [], 'save');


$I->logout();

$I->loginAsStdAdmin();
$I->amOnPage($amendmentUrl);
$I->seeElement('section.likes');
$I->seeElement('button[name=motionLike]');
$I->seeElement('button[name=motionDislike]');
$I->seeElement('button[name=motionSupport]');


$I->wantTo('support this motion');

$I->fillField('input[name=motionSupportName]', 'My name');
$I->fillField('input[name=motionSupportOrga]', 'My organisation');
$I->submitForm('.motionSupportForm', [], 'motionSupport');
$I->see('Du unterstützt diesen Änderungsantrag nun.');
$I->dontSeeElement('button[name=motionSupport]');
$I->see('Du!', 'section.supporters');
$I->dontSee('Testadmin', 'section.supporters');
$I->see('My name', 'section.supporters');
$I->see('My organisation', 'section.supporters');
$I->see('Die Mindestzahl an Unterstützer*innen (1) wurde erreicht');
$I->seeElement('button[name=motionSupportRevoke]');


$I->wantTo('revoke the support');

$I->submitForm('.motionSupportForm', [], 'motionSupportRevoke');
$I->see('Du stehst diesem Änderungsantrag wieder neutral gegenüber');
$I->see('aktueller Stand: 0');


$I->wantTo('support it again');

$I->executeJS('$("[required]").removeAttr("required");');
$I->submitForm('.motionSupportForm', [], 'motionSupport');
$I->dontSee('Du unterstützt diesen Änderungsantrag nun.');
$I->see('No organization entered');

$I->fillField('input[name=motionSupportOrga]', 'My organisation');
$I->submitForm('.motionSupportForm', [], 'motionSupport');
$I->see('Du unterstützt diesen Änderungsantrag nun.');


$I->logout();


$I->wantTo('submit the amendment');

$I->loginAsStdUser();
$I->amOnPage($amendmentUrl);
$I->see('Testadmin', 'section.supporters');
$I->submitForm('.amendmentSupportFinishForm', [], 'amendmentSupportFinish');
$I->see('Der Änderungsantrag ist nun offiziell eingereicht');
$I->see('Eingereicht (ungeprüft)', '.motionData');

$I->logout();


$I->wantTo('ensure I can\'t revoke my support once the amendment has been submitted');
$I->loginAsStdAdmin();
$I->amOnPage($amendmentUrl);
$I->see('Du!', 'section.supporters');
$I->dontSeeElement('button[name=motionSupportRevoke]');
