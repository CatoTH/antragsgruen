<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$page = $I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->wait(0.5);
$I->clickJS('#tagsEditForm .editList li:nth-child(2) .remover');
$I->seeBootboxDialog('Es gibt Anträge oder Änderungsanträge, die diesem Thema zugeordnet sind');
$I->acceptBootboxAlert();
$page->saveForm();
$I->wait(0.5);
$I->seeElement('#consultationSettingsForm');
if ($I->executeJS('return document.querySelectorAll("#tagsEditForm .editList li").length') != 2) {
    $I->fail('Invalid return from tag-List');
}
