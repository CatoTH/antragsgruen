<?php

/** @var \Codeception\Scenario $scenario */

use app\models\policies\UserGroups;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);

$I->populateDBData1();

// Motions: Natural persons disabled; Organizations: only admins
// Amendments: Natural persons: all; Organizations: proposed procedure admins
$I->wantTo('restrict submission as natural person / organization is some strange way');
$page = $I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->clickJS('#sameInitiatorSettingsForAmendments input');
$I->dontSeeElement('#motionSupportersForm .policyWidgetPerson');
$I->dontSeeElement('#motionSupportersForm .policyWidgetOrga');

$I->clickJS('#motionSupportersForm .initiatorSetPermissions input');
$I->seeElement('#motionSupportersForm .policyWidgetPerson');
$I->seeElement('#motionSupportersForm .policyWidgetOrga');

$I->clickJS('#motionSupportersForm .initiatorCanBePerson input');
$I->wait(0.2);
$I->dontSeeElement('#motionSupportersForm .policyWidgetPerson');
$I->seeElement('#motionSupportersForm .policyWidgetOrga');
$I->selectOption('#typeInitiatorOrgaPolicy', '3');


$I->clickJS('#amendmentSupportersForm .initiatorSetPermissions input');
$I->selectOption('#typeAmendmentInitiatorOrgaPolicy', UserGroups::getPolicyID());
$I->wait(0.2);
$I->seeElement('#amendmentSupportersForm .policyWidgetOrga .userGroupSelect');
$I->executeJS('document.querySelector("#typeAmendmentInitiatorOrgaGroups").selectize.addItem(3)');
$I->assertSame(1, $I->executeJS('return document.querySelector("#typeAmendmentInitiatorOrgaGroups").selectize.items.length'));

$page->saveForm();
$I->assertSame(1, $I->executeJS('return document.querySelector("#typeAmendmentInitiatorOrgaGroups").selectize.items.length'));


$I->wantTo('only be able to submit motions as organization as admin');
$I->gotoConsultationHome()->gotoMotionCreatePage();
$I->dontSeeElement('.personTypeSelector');
$I->seeElement('.initiatorData .only-organization');
$I->dontSeeElement('.initiatorData .only-person');
$I->logout();

$I->wantTo('not be able to submit motions as regular user, only as person for amendments');
$I->loginAsStdUser()->gotoConsultationHome()->gotoMotionCreatePage();
$I->dontSeeElement('.personTypeSelector');
$I->seeElement('.noProposerTypeFoundError');

$I->gotoConsultationHome()->gotoAmendmentCreatePage();
$I->dontSeeElement('.personTypeSelector');
$I->seeElement('.initiatorData .only-person');
$I->dontSeeElement('.initiatorData .only-organization');
$I->logout();

$I->wantTo('as proposed procedure admin, both options are available for amendments');
$I->loginAsProposalAdmin()->gotoConsultationHome()->gotoAmendmentCreatePage();
$I->seeElement('.personTypeSelector');
$I->seeElement('.initiatorData .only-person');
$I->dontSeeElement('.initiatorData .only-organization');
$I->clickJS('#personTypeOrga');
$I->dontSeeElement('.initiatorData .only-person');
$I->seeElement('.initiatorData .only-organization');
