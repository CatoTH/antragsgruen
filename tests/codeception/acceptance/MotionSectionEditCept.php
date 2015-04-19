<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use tests\codeception\_pages\ConsultationHomePage;

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('go to the motion section admin page');
$I->gotoStdConsultationHome();
$I->loginAsStdAdmin();
$I->click('#adminLink');
$I->click('.motionSections');
$I->see(mb_strtoupper('Antrags-Abschnitte'), 'h1');

$I->wantTo('Rearrange the list');
$ret = $I->executeJS('return $("#sectionsList").data("sortable").toArray()');
if (json_encode($ret) != '["1","2","4","3","5"]') {
    $I->see('Valid return from JavaScript (1)');
}
$ret = $I->executeJS('$("#sectionsList").data("sortable").sort(["3", "2", "1", "4", "5"])');
$ret = $I->executeJS('return $("#sectionsList").data("sortable").toArray()');
if (json_encode($ret) != '["3","2","1","4","5"]') {
    $I->see('Valid return from JavaScript (2)');
}

$I->submitForm('.adminSectionsForm', [], 'save');

$ret = $I->executeJS('return $("#sectionsList").data("sortable").toArray()');
if (json_encode($ret) != '["3","2","1","4","5"]') {
    $I->see('Valid return from JavaScript (2)');
}

$I->wantTo('check if the change is reflected on the motion');
$I->gotoStdMotion();
$I->see(mb_strtoupper('Begründung'), '.motionTextHolder0 h3');
