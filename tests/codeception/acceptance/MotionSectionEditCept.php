<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use app\models\sectionTypes\ISectionType;

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('go to the motion section admin page');
$I->gotoStdConsultationHome();
$I->loginAsStdAdmin();
$motionSectionsPage = $I->gotoStdAdminPage()->gotoMotionSections(1);

$I->wantTo('rearrange the list');
$ret = $motionSectionsPage->getCurrentOrder();
if (json_encode($ret) != '["1","2","4","3","5"]') {
    $I->see('Got invalid return from JavaScript (1): ' .  json_encode($ret));
}
$motionSectionsPage->setCurrentOrder(array(3, 2, 1, 4, 5));
$ret = $motionSectionsPage->getCurrentOrder();
if (json_encode($ret) != '["3","2","1","4","5"]') {
    $I->see('Got invalid return from JavaScript (2): ' .  json_encode($ret));
}

$motionSectionsPage->saveForm();

$ret = $motionSectionsPage->getCurrentOrder();
if (json_encode($ret) != '["3","2","1","4","5"]') {
    $I->see('Got invalid return from JavaScript (3): ' .  json_encode($ret));
}

$I->wantTo('check if the change is reflected on the motion');
$I->gotoMotion();
$I->see(mb_strtoupper('Begründung'), '.motionTextHolder0 h3');



$I->wantTo('create a tabular data section');
$motionSectionsPage = $I->gotoStdAdminPage()->gotoMotionSections(1);

$I->click('.sectionAdder');
$I->seeElement('.sectionnew0');
$I->dontSee($motionSectionsPage::$tabularLabel, '.sectionnew0 .tabularDataRow');
$I->see($motionSectionsPage::$commentsLabel, '.sectionnew0 .commentRow');

$I->selectOption('.sectionnew0 select.sectionType', ISectionType::TYPE_TABULAR);
$I->see($motionSectionsPage::$tabularLabel, '.sectionnew0 .tabularDataRow');
$I->dontSee($motionSectionsPage::$commentsLabel, '.sectionnew0 .commentRow');

$I->fillField('.sectionnew0 .sectionTitle input', 'Some tabular data');
$I->fillField('.sectionnew0 .tabularDataRow ul li.no0 input', 'Testrow');
$I->fillField('.sectionnew0 .tabularDataRow ul li.no1 input', 'Testrow 2');
$I->fillField('.sectionnew0 .tabularDataRow ul li.no2 input', 'Testrow 3');

$I->wantTo('rearrange the tabular data section');

$ret = $I->executeJS('return $(".sectionnew0 .tabularDataRow ul").data("sortable").toArray()');
if (json_encode($ret) != '["acl","acm","acn"]') {
    $I->see('Got invalid return from JavaScript (4): ' .  json_encode($ret));
}
$order = json_encode(['acl', 'acn', 'acm']);
$I->executeJS('$(".sectionnew0 .tabularDataRow ul").data("sortable").sort(' . $order . ')');

$ret = $I->executeJS('return $(".sectionnew0 .tabularDataRow ul").data("sortable").toArray()');
if (json_encode($ret) != '["acl","acn","acm"]') {
    $I->see('Got invalid return from JavaScript (5): ' .  json_encode($ret));
}
$motionSectionsPage->saveForm();


$I->wantTo('check if the changes to tabular data section were saved');

$I->seeElement('.section20');
$I->seeInField('.section20 .sectionTitle input', 'Some tabular data');
$I->seeInField('.section20 .tabularDataRow ul li.no1 input', 'Testrow 3');



$I->wantTo('change the tabular data afterwards');

$I->fillField('.section20 .sectionTitle input', 'My life');
$I->fillField('.section20 .tabularDataRow ul li.no1 input', 'Birth year');

$motionSectionsPage->saveForm();

$I->seeElement('.section20');
$I->seeInField('.section20 .sectionTitle input', 'My life');
$I->seeInField('.section20 .tabularDataRow ul li.no1 input', 'Birth year');
