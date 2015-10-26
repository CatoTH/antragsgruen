<?

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome()->gotoMotionView(2);

$scenario->incomplete('not implemented yet');
$I->see('dummy');
