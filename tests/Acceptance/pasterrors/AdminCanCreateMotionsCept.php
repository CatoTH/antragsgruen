<?php

/** @var \Codeception\Scenario $scenario */
use app\models\policies\LoggedIn;
use app\models\policies\UserGroups;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('check that admins can always create motions');

$page = $I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->checkOption('.managedUserAccounts input');
$page->saveForm();

// We restrict the creation of motions to the user group "proposed procedure", which the admin is not part of
$I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->dontSeeElement('.policyWidgetMotions .userGroupSelect');
$I->selectOption('#typePolicyMotions', UserGroups::getPolicyID());
$I->wait(0.2);
$I->seeElement('.policyWidgetMotions .userGroupSelect');
$I->executeJS('document.querySelector("#typePolicyMotionsGroups").selectize.addItem(3)');
$I->assertSame(1, $I->executeJS('return document.querySelector("#typePolicyMotionsGroups").selectize.items.length'));

$I->selectOption('#typePolicyAmendments', LoggedIn::getPolicyID());
$I->selectOption('#typePolicyAmendments', LoggedIn::getPolicyID());
$I->submitForm('.adminTypeForm', [], 'save');

$I->wait(0.1);
$I->seeElement('.policyWidgetMotions .userGroupSelect');
$I->assertSame(1, $I->executeJS('return document.querySelector("#typePolicyMotionsGroups").selectize.items.length'));


$I->gotoMotionList();
$I->click('#newMotionBtn');
$I->seeElement('.createMotion1');

$I->gotoConsultationHome();
$I->dontSeeElement('.createMotion');

$I->logout();
$I->loginAsProposalAdmin();
$I->seeElement('.createMotion');
