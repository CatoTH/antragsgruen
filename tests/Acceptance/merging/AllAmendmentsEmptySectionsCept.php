<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('merge the amendments');

$I->gotoConsultationHome()->gotoMotionView(2);
$I->loginAsStdAdmin();
$I->click('.sidebarActions .mergeamendments a');
$I->click('.mergeAllRow .btn-primary');

sleep(1);

$I->see('I-Düpferl-Reita', '#sections_3_0_wysiwyg');
$I->executeJS('CKEDITOR.instances.sections_3_0_wysiwyg.setData("<p>Replaced Text</p>")');
$I->dontSee('I-Düpferl-Reita', '#sections_3_0_wysiwyg');
$I->see('Replaced Text', '#sections_3_0_wysiwyg');

$I->wantTo('remove the text');
$I->executeJS('$(".section3 .removeSection input").prop("checked", true).trigger("change");');

// Save
$I->executeJS('$(".none").remove();'); // for some reason necessary...
$I->executeJS('$("#draftSavingPanel").remove();'); // for some reason necessary...
$I->wait(1);

$I->dontSee('I-Düpferl-Reita');
$I->dontSee('Replaced Text');

$I->submitForm('.motionMergeForm', [], 'save');

$I->dontSee('I-Düpferl-Reita');
$I->dontSee('Replaced Text');


$I->wantTo('change it again');

$I->submitForm('#motionConfirmForm', [], 'modify');

$I->seeCheckboxIsChecked('.section3 .removeSection input');
$I->dontSee('I-Düpferl-Reita');
$I->dontSee('Replaced Text');

$I->executeJS('$(".section3 .removeSection input").prop("checked", false).trigger("change");');
$I->dontSee('I-Düpferl-Reita', '#sections_3_0_wysiwyg');
$I->see('Replaced Text', '#sections_3_0_wysiwyg');

$I->executeJS('$(".section3 .removeSection input").prop("checked", true).trigger("change");');
$I->dontSee('I-Düpferl-Reita', '#sections_3_0_wysiwyg');
$I->dontSee('Replaced Text', '#sections_3_0_wysiwyg');

$I->executeJS('$(".none").remove();'); // for some reason necessary...
$I->executeJS('$("#draftSavingPanel").remove();'); // for some reason necessary...
$I->wait(1);

$I->submitForm('.motionMergeForm', [], 'save');

$I->dontSee('I-Düpferl-Reita');
$I->dontSee('Replaced Text');

$I->submitForm('#motionConfirmForm', [], 'confirm');


$I->executeJS('$("#motionConfirmedForm button").trigger("click");');

$I->dontSee('I-Düpferl-Reita');
$I->dontSee('Replaced Text');
$I->dontSeeElementInDOM('#section_3_0');


$I->wantTo('add a reason');

$I->click('.sidebarActions .mergeamendments a');
sleep(1);
// No Init-site, as we don't have any amendments
$I->seeElement('#paragraphWrapper_2_0');
$I->seeElement('#sections_3_0_wysiwyg');
$data = $I->executeJS('return CKEDITOR.instances.sections_3_0_wysiwyg.getData();');
$I->assertEquals('', $data);

$I->executeJS('CKEDITOR.instances.sections_3_0_wysiwyg.setData("<p>Hi there!</p>");');

$I->see('Hi there!', '#sections_3_0_wysiwyg');

// Save
$I->executeJS('$(".none").remove();'); // for some reason necessary...
$I->executeJS('$("#draftSavingPanel").remove();'); // for some reason necessary...
$I->wait(1);

$I->submitForm('.motionMergeForm', [], 'save');

$I->see('Hi there!');

$I->submitForm('#motionConfirmForm', [], 'confirm');

$I->executeJS('$("#motionConfirmedForm button").trigger("click");');

$I->see('Hi there!', '#section_3_0');
