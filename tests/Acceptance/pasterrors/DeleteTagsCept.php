<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$page = $I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->wait(0.5);
$I->executeJS('document.querySelector("#tagsList select").selectize.removeItem("Verkehr");');
$page->saveForm();
$I->wait(0.5);
$I->seeElement('#consultationSettingsForm');
if ($I->executeJS('return document.querySelector("#tagsList select").selectize.items.length') != 2) {
    $I->fail('Invalid return from tag-List');
}
