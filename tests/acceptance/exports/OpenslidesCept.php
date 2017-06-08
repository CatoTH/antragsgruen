<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('test the list of motions in OpenSlides-Format');
$I->loginAndGotoMotionList();
$I->click('#exportOpenslidesBtn');
$I->seeElement('.exportOpenslidesDd .users');
$file = $I->downloadLink('.exportOpenslidesDd .users');
if (strlen($file) == 0) {
    $I->fail('File has no content');
}
if (mb_strpos($file, 'Hößl') === false) {
    $I->fail('User file has not all content');
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
