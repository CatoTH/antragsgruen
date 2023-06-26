<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->loginAsStdAdmin()->gotoMotion(true, '123-textformatierungen');
$I->click('#sidebar .mergeamendments a');
$I->submitForm('.mergeAllRow', []);
$I->wait(1);

$start = $I->executeJS('return $("#sections_2_5_wysiwyg ol").attr("start")');
$I->assertEquals("4", $start);

$I->executeJS('$(".none").remove();'); // for some reason necessary...
$I->executeJS('$("#draftSavingPanel").remove();'); // for some reason necessary...
$I->wait(1);

$I->submitForm('.motionMergeForm', [], 'save');

$ols = $I->executeJS('return $(".paragraph ol[start=4]").length');
$I->assertEquals(1, $ols);
$text = $I->executeJS('return $(".paragraph ol[start=4]").text()');
$I->assertStringContainsString("Seltsame Zeichen", $text);
