<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBDataDbwv();


$I->wantTo('Antragsberechtigte: Antrag anlegen');
$I->gotoConsultationHome(true, 'std', 'lv-sued');
$I->loginAsDbwvTestUser('lv-sued-antragsberechtigt-0');
$I->see('Antragsberechtigte', '#dbwvUserLoginPanel');
$I->see('Antrag stellen', '.btnCreateMotion');
$I->dontSeeElement('.myMotionList');
$I->click('.btnCreateMotion');

$I->fillField(['name' => 'sections[1]'], 'Testantrag');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData("<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.</p><p>Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>");');
$I->executeJS('CKEDITOR.instances.sections_3_wysiwyg.setData("<p>Es führt kein Weg an Lorem ipsum vorbei</p>");');
$I->executeJS('$("#resolutionDate").val("23.05.2020")');
$I->submitForm('#motionEditForm', [], 'save');
$I->see('Organisation-0 (dort beschlossen am: 23.05.2020)');
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->see('Sie haben den Antrag eingereicht.');
$I->submitForm('#motionConfirmedForm', [], '');
$I->see('Testantrag', '.myMotionList');
$I->logout();


$I->wantTo('AL Recht: Sachgebiet zuordnen');
$I->loginAsDbwvTestUser('al-recht');
$I->see('AL Recht', '#dbwvUserLoginPanel');
$I->see('To Do (1)', '#adminTodo');

// AL Recht does not have extended permissions
$I->click('#motionListLink');
$I->dontSeeElement('.adminMotionListActions');
$I->dontSeeElement('.actionCol');
$I->click('.motion1 .prefixCol a');
$I->seeElement('#dbwv_main_tagSelect');
$I->executeJS('document.getElementById("dbwv_main_tagSelect").value = 47');
$I->trigerChangeJS('#dbwv_main_tagSelect');
$I->see('Gespeichert', '.alert-success');
$I->dontSee('To Do (1)', '#adminTodo');
$I->logout();


$I->wantTo('Referat III: Themengebiet/Nummer vergeben + Redaktionelle Änderung');
$I->loginAsDbwvTestUser('lv-sued-referat-iii');
$I->see('To Do (1)', '#adminTodo');
$I->seeElement('#dbwv_step1_subtagSelect');
$I->dontSeeElement('#dbwv_step1_subtagNew');
$I->executeJS('document.getElementById("dbwv_step1_subtagSelect").value = "new"');
$I->trigerChangeJS('#dbwv_step1_subtagSelect');
$I->seeElement('#dbwv_step1_subtagNew');
$I->fillField('#dbwv_step1_subtagNew', 'Gehalt');
$I->seeInField('#dbwv_step1_prefix', 'III/01');
$I->checkOption('#dbwv_step1_textchanges');
$I->submitForm('#dbwv_step1_assign_number', [], '');

$I->see('Antrag bearbeiten', 'h1');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(CKEDITOR.instances.sections_2_wysiwyg.getData() + "<p>Third paragraph</p>")');
$I->submitForm('#motionEditForm', [], 'save');
$I->submitForm('#motionConfirmedForm', [], '');
$I->seeElement('#dbwv_step1_assign_number');
$I->seeElement('#dbwv_assign_main_tag');
$I->see('V2', '.motionHistory .currentVersion');
$I->clickJS('.motionHistory .historyOpener button');
$I->see('V1', '.motionHistory .otherVersion');
$I->click('.motionHistory .currentVersion .changesLink a');
$I->see('Third paragraph', '.inserted');
$I->dontSee('To Do (1)', '#adminTodo');
$I->logout();


$I->wantTo('Antragsberechtigte: Nur V1 sichtbar');
$I->gotoConsultationHome(true, 'std', 'lv-sued');
$I->loginAsDbwvTestUser('lv-sued-antragsberechtigt-0');
$I->see('III/01: Testantrag', '.myMotionList');
$I->click('.myMotionList .motion1');
$I->see('Es führt kein Weg an Lorem ipsum vorbei');
$I->dontSee('Third paragraph');
$I->dontSeeElement('.motionHistory');
$I->logout();


$I->wantTo('Büroleitung: Antrag freischalten');
$I->loginAsDbwvTestUser('lv-sued-bueroleitung');
$I->gotoConsultationHome(true, 'std', 'lv-sued');
$I->dontSee('Sachgebiet III - Dienst- und Laufbahnrecht');
$I->dontSee('Testantrag');
$I->see('To Do (1)', '#adminTodo');
$I->click('#adminTodo');
$I->see('Antrag freischalten', '.motionScreen2');
$I->click('.motionScreen2 a');
$I->see('V2, Eingereicht (geprüft, unveröffentlicht)', '.motion2');
$I->see('To Do: Antrag freischalten', '.motion2');
$I->clickJS('.adminMotionListActions .markAll');
$I->submitForm('.motionListForm', [], 'screen');
$I->see('V2, Eingereicht', '.motion2');
$I->dontSee('To Do (1)', '#adminTodo');
$I->see('Testantrag');
$I->gotoConsultationHome(true, 'std', 'lv-sued');
$I->see('Sachgebiet III - Dienst- und Laufbahnrecht', '.tagLink47');
$I->click('.tagLink47');
$I->see('Testantrag');
$I->see('Sachgebiet III - Dienst- und Laufbahnrecht', 'h1');
$I->logout();


$I->wantTo('Delegierte: Antrag sehen');
$I->loginAsDbwvTestUser('lv-sued-delegiert-0');
$I->see('Testantrag');
$I->see('Sachgebiet III - Dienst- und Laufbahnrecht', 'h1');
$I->click('.motionLink2');
$I->see('Third paragraph');
$I->dontSeeElement('.motionHistory');
$I->logout();
$I->gotoConsultationHome(true, 'std', 'lv-sued');

$I->wantTo('Andere Antragstellende: Antrag nicht sichtbar');
$I->loginAsDbwvTestUser('lv-sued-antragsberechtigt-1');
$I->dontSeeElement('.tagLink47');
$I->dontSee('Testantrag');
$I->dontSee('Sachgebiet III - Dienst- und Laufbahnrecht');
$I->logout();

$I->wantTo('Antragstellende des Antrags: können Antrag inkl. Historie sehen');
$I->loginAsDbwvTestUser('lv-sued-antragsberechtigt-0');
$I->see('Testantrag');
$I->dontSeeElement('.tagLink47');
$I->dontSee('Sachgebiet III - Dienst- und Laufbahnrecht');
$I->click('.motion2');
$I->see('Third paragraph');
$I->see('V2', '.motionHistory .currentVersion');
$I->clickJS('.motionHistory .historyOpener button');
$I->see('V1', '.motionHistory .otherVersion');
$I->click('.motionHistory .currentVersion .changesLink a');
$I->see('Third paragraph', '.inserted');
$I->logout();
$I->gotoConsultationHome(true, 'std', 'lv-sued');

$I->wantTo('LV-Vorstand: kann Antrag inkl. Historie sehen');
$I->loginAsDbwvTestUser('lv-sued-vorstand');
$I->click('.tagLink47');
$I->click('.motionLink2');
$I->see('Third paragraph');
$I->see('V2', '.motionHistory .currentVersion');
$I->clickJS('.motionHistory .historyOpener button');
$I->see('V1', '.motionHistory .otherVersion');
$I->logout();
$I->gotoConsultationHome(true, 'std', 'lv-sued');

$I->wantTo('Referat: kann nicht mehr bearbeiten');
$I->loginAsDbwvTestUser('lv-sued-referat-iii');
$I->click('.tagLink47');
$I->click('.motionLink2');
$I->dontSeeElement('#dbwv_step1_assign_number');
// $I->dontSeeElement('#dbwv_assign_main_tag'); @TODO Unclear if it's wanted
$I->logout();


$I->wantTo('Ausschuss: Verfahrensvorschlag erarbeiten (ModÜ)');
$I->loginAsDbwvTestUser('lv-sued-ausschuss-iii');
$I->see('To Do (1)', '#adminTodo');
$I->dontSeeElement('#proposedChanges');
$I->dontSeeElement('.motionHistory');
$I->clickJS('.proposedChangesOpener button');
$I->seeElement('#proposedChanges');
$I->wait(0.3);
$I->clickJS('#proposedChanges .proposalStatus6 input');
$I->clickJS('#proposedChanges .saving button');
$I->wait(0.3);
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(CKEDITOR.instances.sections_2_wysiwyg.getData().replace(/Third paragraph/, "Dritter Absatz"))');
$I->submitForm('#proposedChangeTextForm', [], 'save');
$I->dontSee('Lorem ipsum dolor', '#pp_section_2');
$I->see('Dritter Absatz', '#pp_section_2 .inserted');
$I->assertFalse($I->executeJS('return $("#proposedChanges .proposalStatus6 input").prop("disabled")'));
$I->see('To Do (1)', '#adminTodo');
$I->executeJS('$("#proposedChanges input[name=\"setPublicExplanation\"]").prop("checked", true).trigger("change")');
$I->executeJS('$("#proposedChanges textarea[name=\"proposalExplanation\"]").val("Eine Erklärung")');
$I->clickJS('#proposedChanges .saving button');
$I->wait(0.5);
$I->executeJS('$("#proposedChanges input[name=\"proposalVisible\"]").prop("checked", true).trigger("change")');
$I->clickJS('#proposedChanges .saving button');
$I->wait(0.5);
$I->assertTrue($I->executeJS('return $("#proposedChanges .proposalStatus6 input").prop("disabled")'));
$I->assertTrue($I->executeJS('return $("#proposedChanges input[name=\"proposalVisible\"]").prop("disabled")'));
$I->assertTrue($I->executeJS('return $("#proposedChanges textarea[name=\"proposalExplanation\"]").prop("disabled")'));
$I->assertFalse($I->executeJS('return $("#proposedChanges .notificationSettings .notifyProposer").prop("disabled")'));
$I->clickJS('#proposedChanges .notificationSettings .notifyProposer');
$I->seeElement('#proposedChanges .notifyProposerSection textarea');
$I->dontSee('To Do (1)', '#adminTodo');
$I->see('Lorem ipsum dolor', '#pp_section_2');
$I->see('Eine Erklärung', '.motionData .proposedStatusRow');
$I->logout();


$I->wantTo('Redaktionsausschuss: Beschluss erarbeiten');
$I->loginAsDbwvTestUser('lv-sued-redaktion');
$I->see('To Do (1)', '#adminTodo');
$I->clickJS('#dbwv_step3_decide input[name=\"followproposal\"][value=\"yes\"]');
$I->clickJS('#dbwv_step3_decide input[name=\"protocol_public\"][value=\"1\"]');
$I->executeJS('CKEDITOR.instances.dbwv_step3_protocol_wysiwyg.setData("<p>Wortprotokoll</p>")');
$I->submitForm('#dbwv_step3_decide', [], '');
$I->see('V4', '.motionHistory');
$I->dontSee('To Do (1)', '#adminTodo');
$I->dontSeeElement('#pp_section_2');
$I->see('Dritter Absatz', '#section_2_2');
$I->dontSee('Third paragraph', '#section_2_2');
$I->gotoConsultationHome(true, 'std', 'lv-sued');
$I->click('.tagLink47');
$I->dontSeeElement('.resolutionList .motionLink4');
$I->gotoConsultationHome(true, 'std', 'lv-sued');
$I->click('#sidebarResolutions');
$I->click('.tagLink47');
$I->see('Beschlussnummer', '.prefixCol');
$I->see('Testantrag', '.motionLink4');
$I->logout();


$I->wantTo('Koordinierungsausschuss: Übernahme in HV');
$I->loginAsDbwvTestUser('koordinierungsausschuss');
$I->see('To Do (1)', '#adminTodo');
$I->click('#adminTodo');
$I->see('In die Hauptversammlung übernehmen', '.todoDbwvMoveToMain4');
$I->click('.todoDbwvMoveToMain4 a');
$I->clickJS('.motionHistory .historyOpener button');
$I->dontSee('V1', '.motionHistory .otherVersion');
$I->see('V2', '.motionHistory .otherVersion');
$I->submitForm('#dbwv_step4_next', [], '');
$I->see('V5', '.motionHistory');
$I->see('Eingereicht (ungeprüft)', '.motionData .statusRow');
$I->click('#motionListLink');
$I->see('Gehalt (intern)', '.motion5 .tagsCol');
$I->see('Sachgebiet III', '.motion5 .tagsCol');
$I->logout();


$I->wantTo('Arbeitsgruppe: Verfahrensvorschlag erarbeiten (ModÜ)');
$I->loginAsDbwvTestUser('hv-arbeitsgruppe-iii');
$I->see('To Do (1)', '#adminTodo');
$I->click('.motion5 .prefixCol a');
$I->seeElement('#proposedChanges');
$I->clickJS('#proposedChanges .proposalStatus6 input');
$I->clickJS('#proposedChanges .saving button');
$I->wait(0.3);
$I->executeJS('CKEDITOR.instances.sections_5_wysiwyg.setData(CKEDITOR.instances.sections_5_wysiwyg.getData().replace(/Dritter Absatz/, "Vierter Absatz"))');
$I->submitForm('#proposedChangeTextForm', [], 'save');
$I->dontSee('Lorem ipsum dolor', '#pp_section_5');
$I->see('Vierter', '#pp_section_5 ins');
$I->assertFalse($I->executeJS('return $("#proposedChanges .proposalStatus6 input").prop("disabled")'));
$I->dontSeeElement('#adminTodo');
$I->logout();


$I->wantTo('Arbeitsgruppe Leitung: Verfahrensvorschlag veröffentlichen');
$I->loginAsDbwvTestUser('hv-arbeitsgruppe-leitung');
$I->see('To Do (1)', '#adminTodo');
$I->click('#adminTodo');
$I->see('Verfahrensvorschlag veröffentlichen');
$I->click('.todoDbwvSetPp6 a');
$I->executeJS('$("#proposedChanges input[name=\"proposalVisible\"]").prop("checked", true).trigger("change")');
$I->clickJS('#proposedChanges .saving button');
$I->wait(0.5);
$I->assertTrue($I->executeJS('return $("#proposedChanges .proposalStatus6 input").prop("disabled")'));
$I->assertTrue($I->executeJS('return $("#proposedChanges input[name=\"proposalVisible\"]").prop("disabled")'));
$I->assertTrue($I->executeJS('return $("#proposedChanges textarea[name=\"proposalExplanation\"]").prop("disabled")'));
$I->assertFalse($I->executeJS('return $("#proposedChanges .notificationSettings .notifyProposer").prop("disabled")'));
$I->clickJS('#proposedChanges .notificationSettings .notifyProposer');
$I->seeElement('#proposedChanges .notifyProposerSection textarea');
$I->dontSee('To Do (1)', '#adminTodo');
$I->see('Lorem ipsum dolor', '#pp_section_5');
$I->logout();


$I->wantTo('Büroleitung: Antrag freischalten');
$I->loginAsDbwvTestUser('hv-bueroleitung');
$I->gotoConsultationHome(true, 'std', 'hv');
$I->dontSee('Sachgebiet III - Dienst- und Laufbahnrecht');
$I->dontSee('Testantrag');
$I->see('To Do (1)', '#adminTodo');
$I->click('#adminTodo');
$I->see('Antrag freischalten', '.motionScreen6');
$I->click('.motionScreen6 a');
$I->see('V6, Eingereicht (ungeprüft)', '.motion6');
$I->see('To Do: Antrag freischalten', '.motion6');
$I->clickJS('.adminMotionListActions .markAll');
$I->submitForm('.motionListForm', [], 'screen');
$I->see('V6, Eingereicht', '.motion6');
$I->dontSee('To Do (1)', '#adminTodo');
$I->gotoConsultationHome(true, 'std', 'hv');
$I->click('.tagLink55');
$I->see('Testantrag', '.titleCol');
$I->see('Landesversammlung Süddeutschland', '.initiatorCol');
$I->click('.motionLink6');
$I->logout();


$I->wantTo('Redaktionsausschuss: Beschluss erarbeiten');
$I->loginAsDbwvTestUser('hv-redaktion');
$I->see('To Do (1)', '#adminTodo');
$I->clickJS('#dbwv_step6_decide input[name=\"followproposal\"][value=\"yes\"]');
$I->clickJS('#dbwv_step6_decide input[name=\"protocol_public\"][value=\"1\"]');
$I->executeJS('CKEDITOR.instances.dbwv_step6_protocol_wysiwyg.setData("<p>Wortprotokoll</p>")');
$I->submitForm('#dbwv_step6_decide', [], '');
$I->see('V7', '.motionHistory');
$I->dontSee('To Do (1)', '#adminTodo');
$I->dontSeeElement('#pp_section_5');
$I->see('Vierter Absatz', '#section_5_2');
$I->dontSee('Dritter Absatz', '#section_5_2');
$I->gotoConsultationHome(true, 'std', 'hv');
$I->click('.tagLink55');
$I->see('Testantrag', '.motionListTags .motionLink7');
$I->click('.motionLink7');
$I->logout();


$I->wantTo('Beschlussveröffentlichung');
$I->loginAsDbwvTestUser('hv-beschlussfassung');
$I->see('To Do (1)', '#adminTodo');
$I->click('#adminTodo');
$I->see('Beschluss veröffentlichen');
$I->click('.todoDbwvPublishResolution7 a');
$I->seeInField('#dbwv_step7_prefix', 'III/01');
$I->submitForm('#dbwv_step7_publish_resolution', [], '');
$I->dontSee('To Do (1)', '#adminTodo');
$I->dontSee('Begründung');
$I->see('V8: Beschluss im Beschlussumdruck', '.motionHistory .currentVersion');
$I->gotoConsultationHome(true, 'std', 'hv');
$I->click('.tagLink55');
$I->see('Testantrag', '.motionListTags .motionLink7');
$I->gotoConsultationHome(true, 'std', 'hv');
$I->click('#sidebarResolutions');
$I->click('.tagLink55');
$I->see('Testantrag', '.motionLink8');
