<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('enable supporters for amendments, but not motions');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->gotoStdAdminPage()->gotoMotionTypes(1);

$I->dontSeeElement('section.amendmentSupporters');
$I->dontSeeElement('#typeSupportTypeAmendment');
$I->seeCheckboxIsChecked('#sameInitiatorSettingsForAmendments input');
$I->executeJS('$("#sameInitiatorSettingsForAmendments input").prop("checked", false).trigger("change");');
$I->seeElement('section.amendmentSupporters');

$I->selectFueluxOption('#typeSupportTypeAmendment', \app\models\supportTypes\SupportBase::GIVEN_BY_INITIATOR);
$I->fillField('#typeMinSupportersAmendment', '19');
$I->submitForm('.adminTypeForm', [], 'save');

$I->seeFueluxOptionIsSelected('#typeSupportType', \app\models\supportTypes\SupportBase::ONLY_INITIATOR);
$I->dontSeeElement('#typeMinSupporters');
$I->seeFueluxOptionIsSelected('#typeSupportTypeAmendment', \app\models\supportTypes\SupportBase::GIVEN_BY_INITIATOR);
$I->seeInField('#typeMinSupportersAmendment', '19');


$I->wantTo('confirm supporters can not be entered for motions');

$I->gotoConsultationHome();
$I->click('#sidebar .createMotion1');
$I->seeElement('.initiatorData');
$I->dontSeeElement('.supporterData');


$I->wantTo('test the settings for amendments');

$I->gotoConsultationHome();
$I->click('.motionLink58'); // A4: Testantrag
$I->click('.sidebarActions .amendmentCreate a');

$I->seeElement('.supporterData');
$I->seeElement('.fullTextAdder');
$I->dontSeeElement('#fullTextHolder');

$I->click('.fullTextAdder a');
$I->seeElement('#fullTextHolder');

$supporters = [];
for ($s = 0; $s < 19; $s++) {
    $supporters[] = 'Person '  . $s . ', KV ' . $s;
}
$I->fillField('#fullTextHolder textarea', implode('; ', $supporters));
$I->click('#fullTextHolder .fullTextAdd');

$I->fillField('#initiatorPrimaryName', 'Mein Name');
$I->fillField('#initiatorEmail', 'test@example.org');

$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');

$I->gotoMotionList();
$I->click('.amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID . ' .prefixCol a');

$I->see('Person 13', '.supporters');
$I->see('KV 1', '.supporters');
