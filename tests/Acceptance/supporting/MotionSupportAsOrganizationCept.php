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
$I->seeCheckboxIsChecked('#motionSupporterHolder .supporterRow input[type=radio][value="1"]');
$I->dontSeeCheckboxIsChecked('#motionSupporterHolder .supporterRow input[type=radio][value="0"]');
$I->submitForm('#motionUpdateForm', [], 'save');

$I->amOnPage($url);
$I->see('Testorga', '#supporters');
$I->dontSee('Testuser (Testorga)', '#supporters');

$I->wantTo('turn the organization into a natural person and add a second supporter');
$I->click('#sidebar .adminEdit a');
$I->checkOption('#motionSupporterHolder .supporterRow input[type=radio][value="0"]');
$I->click('#motionSupporterHolder .supporterRowAdder');
$I->fillField('#motionSupporterHolder .supporterList > li:last-child .supporterName', 'Second Supporter');
$I->fillField('#motionSupporterHolder .supporterList > li:last-child .supporterOrga', 'Second Orga');
$I->checkOption('#motionSupporterHolder .supporterList > li:last-child input[type=radio][value="1"]');
$I->submitForm('#motionUpdateForm', [], 'save');

$I->amOnPage($url);
$I->see('Testuser (Testorga)', '#supporters');
$I->see('Second Orga', '#supporters');
$I->dontSee('Second Supporter', '#supporters');

$I->gotoMotionList();
$I->see('Unterstützer*innen sammeln (1 + 1 Organisation)', '.adminMotionTable');

$I->wantTo('add an organization without naming a contact person');
$I->amOnPage($url);
$I->click('#sidebar .adminEdit a');
$I->click('#motionSupporterHolder .supporterRowAdder');
$I->checkOption('#motionSupporterHolder .supporterList > li:last-child input[type=radio][value="1"]');
$I->fillField('#motionSupporterHolder .supporterList > li:last-child .supporterOrga', 'Third Orga');
$I->submitForm('#motionUpdateForm', [], 'save');

$I->seeInField('#motionSupporterHolder .supporterList > li:last-child .supporterOrga', 'Third Orga');
$I->seeCheckboxIsChecked('#motionSupporterHolder .supporterList > li:last-child input[type=radio][value="1"]');

$I->amOnPage($url);
$I->see('Third Orga', '#supporters');
