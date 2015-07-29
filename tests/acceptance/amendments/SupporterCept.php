<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('create an amendment with several supporters');
$page = $I->gotoConsultationHome(true, 'bdk', 'bdk');
$I->loginAsStdAdmin();

$I->click('.motionLink4');
$I->click('.sidebarActions .amendmentCreate a');

$I->fillField('#initiatorName', 'Mein Name');
$I->seeInField('#initiatorName', 'Mein Name');
$I->fillField('#initiatorEmail', 'test@example.org');

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
$I->seeInField('#initiatorName', 'Mein Name');
$I->submitForm('#amendmentEditForm', [], 'save');
$I->see('Mein Name');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');

\app\tests\_pages\AmendmentPage::openBy($I, [
    'subdomain' => 'bdk',
    'consultationPath' => 'bdk',
    'motionId' => 4,
    'amendmentId' => AcceptanceTester::FIRST_FREE_AMENDMENT_ID + 1
]);

$I->see('Mein Name', '.motionData');
$I->see('Person 13', '.supporters');
$I->see('KV 1', '.supporters');
