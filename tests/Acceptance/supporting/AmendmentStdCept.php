<?php

/** @var \Codeception\Scenario $scenario */
use app\models\supportTypes\SupportBase;
use Tests\Support\AcceptanceTester;

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

$I->selectOption('#typeSupportTypeAmendment', SupportBase::GIVEN_BY_INITIATOR);
$I->fillField('#typeMinSupportersAmendment', '19');
$I->submitForm('.adminTypeForm', [], 'save');

$I->seeOptionIsSelected('#typeSupportType', 'Nur die Antragsteller*in');
$I->dontSeeElement('#typeMinSupporters');
$I->seeOptionIsSelected('#typeSupportTypeAmendment', 'Von der Antragsteller*in angegeben');
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
$I->dontSeeElement('#supporterFullTextHolder');

$I->click('.fullTextAdder button');
$I->seeElement('#supporterFullTextHolder');

$supporters = [];
for ($s = 0; $s < 19; $s++) {
    $supporters[] = 'Person '  . $s . ', KV ' . $s;
}
$I->fillField('#supporterFullTextHolder textarea', implode('; ', $supporters));
$I->click('#supporterFullTextHolder .fullTextAdd');

$I->fillField('#initiatorPrimaryName', 'Mein Name');
$I->fillField('#initiatorEmail', 'test@example.org');

$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');

$I->gotoMotionList();
$I->click('.amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID . ' .prefixCol a');

$I->see('Person 13', '.supporters');
$I->see('KV 1', '.supporters');
