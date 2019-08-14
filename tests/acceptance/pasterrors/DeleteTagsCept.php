<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$page = $I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->executeJS("$('#tagsList').pillbox('removeByText', 'Verkehr');");
$page->saveForm();
$I->seeElement('#consultationSettingsForm');
if ($I->executeJS('return $("#tagsList").pillbox("items").length') !== 2) {
    $I->fail('Invalid return from tag-List');
}
