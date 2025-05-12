<?php

use app\models\sectionTypes\ISectionType;
use Tests\Support\AcceptanceTester;

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('go to the motion section admin page');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$motionTypePage = $I->gotoStdAdminPage()->gotoMotionTypes(1);

$I->wantTo('rearrange the list');
$ret = $motionTypePage->getCurrentOrder();
if (json_encode($ret)!=='["1","2","4","3","5"]') {
    $I->fail('Got invalid return from JavaScript (1): ' .  json_encode($ret));
}
$motionTypePage->setCurrentOrder(array(3, 2, 1, 4, 5));
$ret = $motionTypePage->getCurrentOrder();
if (json_encode($ret)!=='["3","2","1","4","5"]') {
    $I->fail('Got invalid return from JavaScript (2): ' .  json_encode($ret));
}

$motionTypePage->saveForm();

$ret = $motionTypePage->getCurrentOrder();
if (json_encode($ret)!=='["3","2","1","4","5"]') {
    $I->fail('Got invalid return from JavaScript (3): ' .  json_encode($ret));
}

$I->wantTo('check if the change is reflected on the motion');
$I->gotoMotion();
$I->see(mb_strtoupper('Begründung'), '.motionTextHolder0 h2');



$I->wantTo('create a tabular data section');
$motionTypePage = $I->gotoStdAdminPage()->gotoMotionTypes(1);

$I->click('.sectionAdder');
$I->wait(1);
$I->seeElement('.sectionnew0');
$I->dontSee($motionTypePage::$tabularLabel, '.sectionnew0 .tabularDataRow');
$I->dontSee($motionTypePage::$commentsLabel, '.sectionnew0 .commentRow');
$I->selectOption('.sectionnew0 select.sectionType', ISectionType::TYPE_TEXT_SIMPLE);
$I->see($motionTypePage::$commentsLabel, '.sectionnew0 .commentRow');

$I->selectOption('.sectionnew0 select.sectionType', ISectionType::TYPE_TABULAR);
$I->see($motionTypePage::$tabularLabel, '.sectionnew0 .tabularDataRow');
$I->dontSee($motionTypePage::$commentsLabel, '.sectionnew0 .commentRow');

$I->fillField('.sectionnew0 .sectionTitle input', 'Some tabular data');
$I->fillField('.sectionnew0 .tabularDataRow ul li.no0 input', 'Testrow');
$I->fillField('.sectionnew0 .tabularDataRow ul li.no1 input', 'Testrow 2');
$I->fillField('.sectionnew0 .tabularDataRow ul li.no2 input', 'Testrow 3');
$I->selectOption('.sectionnew0 .positionRow input', "1");

$I->wantTo('rearrange the tabular data section');

$ret = $I->executeJS('return $(".sectionnew0 .tabularDataRow ul").data("sortable").toArray()');
if (json_encode($ret)!=='["ewb","ewc","ewd"]') {
    $I->fail('Got invalid return from JavaScript (4): ' .  json_encode($ret));
}
$order = json_encode(['ewb', 'ewd', 'ewc']);
$I->executeJS('$(".sectionnew0 .tabularDataRow ul").data("sortable").sort(' . $order . ')');

$ret = $I->executeJS('return $(".sectionnew0 .tabularDataRow ul").data("sortable").toArray()');
if (json_encode($ret)!=='["ewb","ewd","ewc"]') {
    $I->fail('Got invalid return from JavaScript (5): ' .  json_encode($ret));
}
$motionTypePage->saveForm();


$I->wantTo('check if the changes to tabular data section were saved');

$newCss = '.section' . AcceptanceTester::FIRST_FREE_MOTION_SECTION;
$I->seeElement($newCss);
$I->seeInField($newCss . ' .sectionTitle input', 'Some tabular data');
$I->seeInField($newCss . ' .tabularDataRow ul li.no1 input', 'Testrow 3');
$I->seeOptionIsSelected($newCss . ' .positionRow input', "1");


$I->wantTo('change the tabular data afterwards');

$I->fillField($newCss . ' .sectionTitle input', 'My life');
$I->fillField($newCss . ' .tabularDataRow ul li.no1 input', 'Birth year');

$motionTypePage->saveForm();

$I->seeElement($newCss);
$I->seeInField($newCss . ' .sectionTitle input', 'My life');
$I->seeInField($newCss . ' .tabularDataRow ul li.no1 input', 'Birth year');
