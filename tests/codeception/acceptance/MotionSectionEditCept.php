<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('go to the motion section admin page');
$I->gotoStdConsultationHome();
$I->loginAsStdAdmin();
$motionSectionsPage = $I->gotoStdAdminPage()->gotoMotionSections();

$I->wantTo('Rearrange the list');
$ret = $motionSectionsPage->getCurrentOrder();
if (json_encode($ret) != '["1","2","4","3","5"]') {
    $I->see('Valid return from JavaScript (1)');
}
$motionSectionsPage->setCurrentOrder(array(3, 2, 1, 4, 5));
$ret = $motionSectionsPage->getCurrentOrder();
if (json_encode($ret) != '["3","2","1","4","5"]') {
    $I->see('Valid return from JavaScript (2)');
}

$motionSectionsPage->saveForm();

$ret = $motionSectionsPage->getCurrentOrder();
if (json_encode($ret) != '["3","2","1","4","5"]') {
    $I->see('Valid return from JavaScript (2)');
}

$I->wantTo('check if the change is reflected on the motion');
$I->gotoStdMotion();
$I->see(mb_strtoupper('Begründung'), '.motionTextHolder0 h3');
