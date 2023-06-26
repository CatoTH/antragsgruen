<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('test the list of motions in OpenSlides-Format');
$I->loginAndGotoMotionList();
$I->dontSeeElement('#exportOpenslidesBtn');

$I->dontSeeElement('.activateOpenslides');
$I->click('#activateFncBtn');
$I->seeElement('.activateOpenslides');
$I->click('.activateOpenslides');
$I->seeElement('#exportOpenslidesBtn');

$I->click('#exportOpenslidesBtn');
$I->seeElement('.exportOpenslidesDd .users');
$file = $I->downloadLink('.exportOpenslidesDd .users');
if (strlen($file) === 0) {
    $I->fail('File has no content');
}
if (mb_strpos($file, 'Lischke') === false) {
    $I->fail('User file has not all content: ' . $file);
}

$file = $I->downloadLink('.exportOpenslidesDd .slidesMotionType1');
if (strlen($file) == 0) {
    $I->fail('File has no content');
}
if (mb_strpos($file, 'line-through') === false) {
    $I->fail('Motion file has not all content');
}

$file = $I->downloadLink('.exportOpenslidesDd .amendments');
if (strlen($file) == 0) {
    $I->fail('File has no content');
}
if (mb_strpos($file, 'Von Zeile 9 bis 10') === false) {
    $I->fail('Amendment file has not all content: ' . $file);
}
