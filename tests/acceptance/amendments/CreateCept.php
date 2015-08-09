<?php

use app\tests\_pages\MotionPage;

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();


// Load Form

$I->wantTo('ensure the amendment does not exist yet');
MotionPage::openBy(
    $I,
    [
        'subdomain'        => 'stdparteitag',
        'consultationPath' => 'std-parteitag',
        'motionId'         => 2,
    ]
);
$I->see('A2: O’ZAPFT IS!', 'h1');
$I->dontSee(AcceptanceTester::FIRST_FREE_AMENDMENT_TITLE_PREFIX, 'section.amendments ul.amendments');


$I->wantTo('open the amendment creation page');
$I->see(mb_strtoupper('Änderungsantrag stellen'), '.sidebarActions');
$I->click('.sidebarActions .amendmentCreate a');

$I->see('Antrag', '.breadcrumb');
$I->see(mb_strtoupper('Änderungsantrag zu A2 stellen'), 'h1');
$I->seeInField('#sections_1', 'O’zapft is!');
$I->see('woschechta Bayer', '#section_holder_2');


$I->wantTo('modify the motion text');

$I->dontSee('JavaScript aktiviert sein');
$I->see('Gremium, LAG...');
$I->dontSee('Beschlussdatum');
$I->selectOption('#personTypeOrga', \app\models\db\ISupporter::PERSON_ORGANIZATION);
$I->dontSee('Gremium, LAG...');
$I->see('Beschlussdatum');

$I->executeJS('window.newText = CKEDITOR.instances.sections_2_wysiwyg.getData();');
$I->executeJS('window.newText = window.newText.replace(/woschechta Bayer/g, "Sauprei&szlig;");');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(window.newText);');
$I->executeJS('CKEDITOR.instances.amendmentReason_wysiwyg.setData("<p>This is my reason</p>");');

$I->dontSee('woschechta Bayer', '#section_holder_2');
$I->see('Saupreiß', '#section_holder_2');

$I->fillField('#sections_1', 'New title');

$I->dontSeeElement('.editorialChange .wysiwyg-textarea');
$I->click('.editorialChange .opener');
$I->seeElement('.editorialChange .wysiwyg-textarea');
$I->executeJS('CKEDITOR.instances.amendmentEditorial_wysiwyg.setData("<p>some meta text</p>");');

$I->wantTo('submit the amendment with missing contact information');

$I->fillField(['name' => 'Initiator[name]'], 'My Name');
$I->fillField(['name' => 'Initiator[contactEmail]'], 'test@example.org');
$I->selectOption('#personTypeOrga', \app\models\db\ISupporter::PERSON_ORGANIZATION);
$I->submitForm('#amendmentEditForm', [], 'save');

$I->seeBootboxDialog('Es muss ein Beschlussdatum angegeben werden');
$I->acceptBootboxAlert();

$I->seeInField('#sections_1', 'New title');
$I->see('Saupreiß', '#section_holder_2');
$I->see('This is my reason', '#amendmentReasonHolder');

$I->seeInField(['name' => 'Initiator[name]'], 'My Name');
$I->seeInField(['name' => 'Initiator[contactEmail]'], 'test@example.org');
$I->dontSeeCheckboxIsChecked("#personTypeNatural");
$I->seeCheckboxIsChecked("#personTypeOrga");
$I->dontSee('Gremium, LAG...');
$I->see('Beschlussdatum');


$I->wantTo('enter the missing data and submit the amendment');

$I->dontSeeElement('.bootstrap-datetimepicker-widget');
$I->executeJS('$("#resolutionDateHolder").find(".input-group-addon").click()');
$I->seeElement('.bootstrap-datetimepicker-widget');
$I->executeJS('$("#resolutionDateHolder").find(".input-group-addon").click()');
$I->dontSeeElement('.bootstrap-datetimepicker-widget');

$I->fillField(['name' => 'Initiator[name]'], 'My company');
$I->fillField(['name' => 'Initiator[resolutionDate]'], '12.01.2015');
$I->submitForm('#amendmentEditForm', [], 'save');
$I->see(mb_strtoupper('Änderungsantrag bestätigen'), 'h1');

$I->wantTo('not confirm the amendment, instead correcting a mistake');

$I->submitForm('#amendmentConfirmForm', [], 'modify');
$I->see(mb_strtoupper('Änderungsantrag zu A2 stellen'), 'h1');
$I->seeInField(['name' => 'Initiator[name]'], 'My company');
$I->seeInField(['name' => 'Initiator[resolutionDate]'], '12.01.2015');
$I->see('some meta text', '#section_holder_editorial');

$I->executeJS('CKEDITOR.instances.amendmentReason_wysiwyg.setData("<p>This is my extended reason</p>");');

$I->submitForm('#amendmentEditForm', [], 'save');
$I->see(mb_strtoupper('Änderungsantrag bestätigen'), 'h1');
$I->see('This is my extended reason', '.amendmentReasonHolder');
$I->see('some meta text');

$I->wantTo('submit the final amendment');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');
$I->see(mb_strtoupper('Änderungsantrag eingereicht'), 'h1');
$I->see('Du hast den Änderungsantrag eingereicht. Er ist jetzt sofort sichtbar.');

$I->wantTo('see the amendment on the start page');
$I->gotoConsultationHome();
$I->see(AcceptanceTester::FIRST_FREE_AMENDMENT_TITLE_PREFIX, '.motionListStd .amendments');
$I->see('My company', '.motionListStd .amendments');


$I->wantTo('see the amendment on the motion page');
MotionPage::openBy(
    $I,
    [
        'subdomain'        => 'stdparteitag',
        'consultationPath' => 'std-parteitag',
        'motionId'         => 2,
    ]
);
$I->see('A2: O’ZAPFT IS!', 'h1');
$I->see(AcceptanceTester::FIRST_FREE_AMENDMENT_TITLE_PREFIX, 'section.amendments ul.amendments');


$I->wantTo('open the amenmdent page');
$I->click('section.amendments ul.amendments a.amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);

$I->see(mb_strtoupper(AcceptanceTester::FIRST_FREE_AMENDMENT_TITLE_PREFIX . ' zu A2: O’ZAPFT IS!'), 'h1');
$I->see('My company', '.motionDataTable');
$I->see('woschechta Bayer', '#section_2_0 del');
$I->see('Saupreiß', '#section_2_0 ins');
$I->see('This is my extended reason', '#amendmentExplanation');
$I->see('some meta text');
