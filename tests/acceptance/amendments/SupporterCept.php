<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('create an amendment with several supporters');
$page = $I->gotoConsultationHome(true, 'bdk', 'bdk');
$I->loginAsStdAdmin();

$I->click('.motionLink4');
$I->click('.sidebarActions .amendmentCreate a');

$I->seeElement('.supporterData');
$I->seeElement('.fullTextAdder');
$I->dontSeeElement('#fullTextHolder');

$I->click('.fullTextAdder a');
$I->seeElement('#fullTextHolder');

$supporters = [];
for ($s = 0; $s < 20; $s++) {
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
