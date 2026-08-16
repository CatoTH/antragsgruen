<?php

/** @var \Codeception\Scenario $scenario */
use app\models\policies\IPolicy;
use app\models\supportTypes\SupportBase;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);

$I->populateDBData1();

$I->wantTo('allow supporting as organization');
$page = $I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->dontSeeElement('#typeSupporterCanBeOrga');
$I->selectOption('#typeSupportType', SupportBase::COLLECTING_SUPPORTERS);
$I->seeElement('#typeSupporterCanBeOrga');
$I->seeCheckboxIsChecked('#typeSupporterCanBePerson');
$I->checkOption('#typeSupporterCanBeOrga');
$I->uncheckOption('#typeHasOrga');
$I->selectOption('#typePolicySupportMotions', IPolicy::POLICY_LOGGED_IN);
$I->fillField('#typeMinSupporters', 3);
$I->checkOption('.motionSupport');

$page->saveForm();

$createPage = $I->gotoConsultationHome()->gotoMotionCreatePage();
$createPage->fillInValidSampleData();
$createPage->saveForm();
$I->submitForm('#motionConfirmForm', [], 'confirm');
$url = $I->executeJS('return $("#urlSharing").val();');

$I->wantTo('support this motion as an organization');
$I->gotoConsultationHome();
$I->logout();
$I->loginAsStdUser();
$I->amOnPage($url);

$I->seeElement('.supportBlock');
$I->seeElement('.supportPersonTypeSelection');
$I->checkOption('.supportPersonTypeSelection .supportAsOrga input');
$I->fillField('.supportBlock .colOrga input', 'Testorga');
$I->submitForm('.motionSupportForm', [], 'motionSupport');

$I->see('Testorga', '#supporters');
$I->dontSee('Testuser (Testorga)', '#supporters');

$I->wantTo('see the organization counted separately in the admin motion list');
$I->logout();
$I->loginAsStdAdmin();
$I->gotoMotionList();
$I->see('Unterstützer*innen sammeln (1 Organisation)', '.adminMotionTable');

$I->wantTo('see the organization in the admin form, without changing it when saving');
$I->amOnPage($url);
$I->click('#sidebar .adminEdit a');
$I->seeInField('#motionSupporterHolder .supporterRow .supporterOrga', 'Testorga');
$I->submitForm('#motionUpdateForm', [], 'save');

$I->amOnPage($url);
$I->see('Testorga', '#supporters');
$I->dontSee('Testuser (Testorga)', '#supporters');
