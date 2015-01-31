<?php

use tests\codeception\_pages\ManagerStartPage;

$I = new AcceptanceTester($scenario);
$I->wantTo('ensure that ManagerStartPage works');
ManagerStartPage::openBy($I);
$I->see('Congratulations!', 'h1');
