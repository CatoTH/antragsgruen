<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('edit an initiator');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->selectOption('#typeInitiatorForm', \app\models\initiatorForms\WithSupporters::getTitle());
$I->submitForm('.adminTypeForm', [], 'save');

$page = $I->gotoStdAdminPage()->gotoMotionList()->gotoAmendmentEdit(2);
$I->see('E-Mail: testuser@example.org', '.supporterForm');
$I->dontSeeElement('#initiatorOrga');
$I->selectOption('#personTypeOrga', \app\models\db\ISupporter::PERSON_NATURAL);
$I->fillField('#initiatorName', 'Another test user');
$I->fillField('#initiatorOrga', 'KV Test');
$I->fillField('#initiatorEmail', 'test2@example.org');
$I->fillField('#initiatorPhone', '01234567');

$I->dontSeeElement('.initiatorData .initiatorRow');
$I->executeJS('$(".initiatorData .adderRow a").click();');
$I->seeElement('.initiatorData .initiatorRow');
$I->fillField('.initiatorData .initiatorRow .name', 'My Friend');
$I->fillField('.initiatorData .initiatorRow .organization', 'Her KV');


$I->wantTo('add some suporters using full-text');

$I->dontSeeElement('#fullTextHolder');
$I->click('.fullTextAdder a');
$I->seeElement('#fullTextHolder');

$I->fillField('#fullTextHolder textarea', 'Tobias Hößl, KV München; Test 2');
$I->click('#fullTextHolder .fullTextAdd');


$I->submitForm('#amendmentUpdateForm', [], 'save');

$I->wantTo('confirm the changes are saved');

$page = $I->gotoStdAdminPage()->gotoMotionList()->gotoAmendmentEdit(2);
$I->see('E-Mail: testuser@example.org', '.supporterForm');
$I->seeInField('#initiatorName', 'Another test user');
$I->seeInField('#initiatorOrga', 'KV Test');
$I->seeInField('#initiatorEmail', 'test2@example.org');
$I->seeInField('#initiatorPhone', '01234567');

$I->seeElement('.initiatorData .initiatorRow');
$I->seeInField('.initiatorData .initiatorRow .name', 'My Friend');
$I->seeInField('.initiatorData .initiatorRow .organization', 'Her KV');

$name1 = $I->executeJS('return $(".supporterRow").eq(0).find("input.name").val()');
$orga1 = $I->executeJS('return $(".supporterRow").eq(0).find("input.organization").val()');
$name2 = $I->executeJS('return $(".supporterRow").eq(1).find("input.name").val()');
if ($name1 != 'Tobias Hößl' || $orga1 != 'KV München' || $name2 != 'Test 2') {
    $I->fail('Got invalid supporter Data: ' . $name1 . ' (' . $orga1 . ') / ' . $name2);
}
