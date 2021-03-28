<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);

$I->populateDBData1();

$I->wantTo('enable non-public supports');
$page = $I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->dontSeeElement('#typeOfferNonPublicSupports');
$I->selectFueluxOption('#typeSupportType', \app\models\supportTypes\SupportBase::COLLECTING_SUPPORTERS);
$I->seeElement('#typeOfferNonPublicSupports');
$I->checkOption("#typeOfferNonPublicSupports");
$I->selectFueluxOption('#typePolicySupportAmendments', \app\models\policies\IPolicy::POLICY_LOGGED_IN);
$I->fillField('#typeMinSupporters', 3);
$I->checkOption('.amendmentSupport');


$page->saveForm();

$createPage = $I->gotoConsultationHome()->gotoAmendmentCreatePage();
$createPage->fillInValidSampleData();
$createPage->saveForm();
$I->submitForm('#amendmentConfirmForm', [], 'confirm');
$url = $I->executeJS('return $("#urlSharing").val();');

$I->wantTo('support this amendment non-publically');
$I->gotoConsultationHome();
$I->logout();
$I->loginAsStdUser();
$I->amOnPage($url);

$I->seeElement('.supportBlock');
$I->seeElement('.nonPublicBlock');
$I->seeCheckboxIsChecked('.nonPublicBlock input');
$I->uncheckOption('.nonPublicBlock input');
$I->fillField('.supportBlock .colOrga input', 'Testorga');
$I->submitForm('.motionSupportForm', [], 'motionSupport');

$I->see('Testuser (Testorga)', '.supportersList');
$I->see('(Nur für eingeloggte sichtbar)', '.supportersList');

$I->logout();
$I->dontSee('Testuser (Testorga)', '.supportersList');
$I->see('1 Unterstützer*in', '#supporters');
