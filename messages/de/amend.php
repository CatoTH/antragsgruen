<?php

return [
    'amendment'                         => 'Änderungsantrag',
    'amendments'                        => 'Änderungsanträge',
    'motion'                            => 'Antrag',
    'initiator'                         => 'Antragsteller*in',
    'initiators_title'                  => 'Antragsteller*innen',
    'supporter'                         => 'Unterstützer*in',
    'supporters'                        => 'Unterstützer*innen',
    'supporters_title'                  => 'Unterstützer*innen',
    'supporter_you'                     => 'Du!',
    'supporter_none'                    => 'keine',
    'status'                            => 'Status',
    'resoluted_on'                      => 'Entschieden am',
    'submitted_on'                      => 'Eingereicht',
    'comments_title'                    => 'Kommentare',
    'comments_screening_queue_1'        => '1 Kommentar wartet auf Freischaltung',
    'comments_screening_queue_x'        => '%NUM% Kommentare warten auf Freischaltung',
    'comments_please_log_in'            => 'Logge dich ein, um kommentieren zu können.',
    'prefix'                            => 'Antragsnummer',
    'none_yet'                          => 'Es gibt noch keine Änderungsanträge',
    'amendment_for'                     => 'Änderungsantrag zu',
    'amendment_for_prefix'              => 'Änderungsantrag zu %PREFIX%',
    'confirmed_visible'                 => 'Du hast den Änderungsantrag eingereicht. Er ist jetzt sofort sichtbar.',
    'confirmed_screening'               => 'Du hast den Änderungsantrag eingereicht. ' .
        'Er wird nun auf formale Richtigkeit geprüft und dann freigeschaltet.',
    'confirmed_support_phase'           => 'Du hast den Änderungsantrag eingestellt.<br>
        Um ihn offiziell einzureichen, benötigt er nun <strong>mindestens %MIN% Unterstützer*innen</strong>.<br><br>
        Du kannst Interessierten folgenden Link schicken, damit sie den Änderungsantrag dort unterstützen können:',
    'submitted_adminnoti_title'         => 'Neuer Änderungsantrag',
    'submitted_adminnoti_body'          => "Es wurde ein neuer Änderungsantrag eingereicht.\nAntrag: %TITLE%\nAntragsteller*in: %INITIATOR%\nLink: %LINK%",
    'submitted_screening_email'         => "Hallo,\n\ndu hast soeben einen Änderungsantrag eingereicht.\n" .
        "Der Antrag wird nun auf formale Richtigkeit geprüft und dann freigeschaltet. " .
        "Du wirst dann gesondert darüber benachrichtigt.\n\n" .
        "Du kannst ihn hier einsehen: %LINK%\n\n" .
        "Mit freundlichen Grüßen,\n" .
        "  Das Antragsgrün-Team",
    'submitted_screening_email_subject' => 'Änderungsantrag eingereicht',
    'screened_hint'                     => 'Geprüft',
    'amend_for'                         => ' zu ',
    'create_explanation'                => 'Ändere hier den Antrag so ab, wie du ihn gern sehen würdest.<br>' . "\n" .
        'Unter &quot;<strong>Begründung</strong>&quot; kannst du die Änderung begründen.<br>' . "\n" .
        'Falls dein Änderungsantrag Hinweise an die Programmkommission enthält, kannst du diese als ' . "\n" .
        '&quot;<strong>Redaktionelle Änderung</strong>&quot; beifügen.',
    'support_collect_explanation_title' => 'Unterstützer*innen sammeln',
    'support_collect_explanation'       => 'Änderungsanträge, die nicht von Gremien gestellt werden, müssen mindestens %MIN% Unterstützer*innen finden, um zugelassen zu werden. Um einen solchen Änderungsantrag einzureichen, gehe wie folgt vor:<br>
<ol>
<li><strong>Entwurf</strong>: Trage zunächst hier den Änderungsantrag ein und hinterlege deine Kontaktdaten. Bestätige auf der nächsten Seite, das du den Änderungsantrag einbringen willst.</li>
<li><strong>Untertützung</strong>: Du erhältst dann einen Link, den du an potentielle Interessierte schicken kannst. Jeder, der den Link kennt, kann den Entwurf einsehen. Jede*r Delegierte*r kann den Änderungsantrag nun hier auf Antragsgrün unterstützen.</li>
<li><strong>Änderungsantrag einbringen</strong>: Sobald sich %MIN% Unterstützer*innen gefunden haben, bekommst du eine Benachrichtigungs-E-Mail. Ab dann kannst du den Änderungsantrag offiziell einbringen. Auch danach ist es noch möglich, dass sich Unterstützer*innen für diesen Änderungsantrag eintragen.</li>
</ol>',
    'editorial_hint'                    => 'Redaktionelle Änderung',
    'merge_amend_stati'                 => 'Status der Änderungsanträge',
    'merge_bread'                       => 'Überarbeiten',
    'merge_title'                       => '%TITLE% überarbeiten',
    'merge_new_text'                    => 'Neuer Antragstext',
    'merge_confirm_title'               => 'Überarbeitung kontrollieren',
    'merge_submitted'                   => 'Überarbeitung eingereicht',
    'merge_submitted_title'             => '%TITLE% überarbeitet',
    'merge_submitted_str'               => 'Der Antrag wurde überarbeitet',
    'merge_submitted_to_motion'         => 'Zum neuen Antrag',
    'merge_colliding'                   => 'Kollidierender Änderungsantrag',
    'merge_accept_all'                  => 'Alle Änderungen übernehmen',
    'merge_reject_all'                  => 'Alle Änderungen ablehnen',
    'merge_explanation'                 => 'Hier wird der Text mitsamt allen Änderungsanträgen im Text angezeigt. ' .
        'Du kannst bei jeder Änderung angeben, ob du sie <strong>annehmen oder ablehnen</strong> willst - klicke dazu einfach mit der rechten Maustaste auf die Änderung und wähle "Annehmen" oder "Ablehnen" aus.<br><br>' .
        'Über das Annehmen und Ablehnen von Änderungsanträgen hinaus kannst du den Text auch <strong>frei bearbeiten</strong>, um dadurch redaktionelle Änderungen durchzuführen.<br>###COLLIDINGHINT###<br><br>' .
        'Anschließend kannst du den neuen Status der Änderungsanträge auswählen und dann auf "Weiter" klicken. Dadurch wird ein <strong>neuer Antrag "###NEWPREFIX###"</strong> erzeugt. Der ursprüngliche Antrag sowie die Änderungsanträge bleiben zur Referenz erhalten, werden aber als "veraltet" markiert.',
    'merge_explanation_colliding'       => '<br><span class="glyphicon glyphicon-warning-sign" style="float: left; font-size: 2em; margin: 10px;"></span> Da es zu diesem Antrag mehrere Änderungsanträge gibt, die sich auf die selbe Textstelle beziehen - <strong>kollidierende Änderungsanträge</strong> - ist es notwendig, diese Änderungsanträge händisch einzupflegen. Lösche bitte anschließend den kollidierenden Änderungsantrag, indem du ihn zunächst mit der Entfernen/Del-Taste löschst, und diese Änderung dann mit der rechten Maustaste annimmst.',
    'unsaved_drafts'                    => 'Es gibt noch ungespeicherte Entwürfe, die wiederhergestellt werden können:',
    'confirm_amendment'                 => 'Änderungsantrag bestätigen',
    'amendment_submitted'               => 'Änderungsantrag eingereicht',
    'amendment_create'                  => 'Änderungsantrag stellen',
    'amendment_edit'                    => 'Änderungsantrag bearbeiten',
    'amendment_create_x'                => 'Änderungsantrag zu %prefix% stellen',
    'amendment_edit_x'                  => 'Änderungsantrag zu %prefix% bearbeiten',
    'amendment_withdraw'                => 'Änderungsantrag zurückziehen',
    'edit_done'                         => 'Änderungsantrag bearbeitet',
    'edit_done_msg'                     => 'Die Änderungen wurden übernommen.',
    'edit_bread'                        => 'Bearbeiten',
    'reason'                            => 'Begründung',
    'amendment_requirement'             => 'Voraussetzungen für einen Änderungsantrag',
    'button_submit'                     => 'Einreichen',
    'button_correct'                    => 'Korrigieren',
    'confirm'                           => 'Bestätigen',
    'go_on'                             => 'Weiter',
    'published_email_body'              => "Hallo,\n\ndein Änderungsantrag zu %MOTION% wurde soeben auf Antragsgrün veröffentlicht. " .
        "Du kannst ihn hier einsehen: %LINK%\n\n" .
        "Mit freundlichen Grüßen,\n" .
        "  Das Antragsgrün-Team",
    'published_email_title'             => 'Änderungsantrag veröffentlicht',
    'sidebar_adminedit'                 => 'Admin: bearbeiten',
    'sidebar_back'                      => 'Zurück zum Antrag',
    'back_to_amend'                     => 'Zurück zum Änderungsantrag',
    'initiated_by'                      => 'gestellt von',
    'confirm_bread'                     => 'Bestätigen',
    'affects_x_paragraphs'              => 'Bezieht sich auf insgesamt %num% Absätze',
    'singlepara_revert'                 => 'Änderungen rückgängig machen',
    'err_create_permission'             => 'Keine Berechtigung zum Anlegen von Änderungsanträgen.',
    'err_create'                        => 'Ein Fehler beim Anlegen ist aufgetreten',
    'err_save'                          => 'Ein Fehler beim Speichern ist aufgetreten',
    'err_type_missing'                  => 'Du musst einen Typ angeben.',
    'err_not_found'                     => 'Der Änderungsantrag wurde nicht gefunden',
    'err_withdraw_forbidden'            => 'Not allowed to withdraw this motion.',
    'err_edit_forbidden'                => 'Not allowed to edit this motion.',
    'withdraw_done'                     => 'Der Änderungsantrag wurde zurückgezogen.',
    'withdraw_bread'                    => 'Zurückziehen',
    'withdraw'                          => 'Zurückziehen',
    'withdraw_confirm'                  => 'Willst du diesen Änderungsantrag wirklich zurückziehen?',
    'withdraw_no'                       => 'Doch nicht',
    'withdraw_yes'                      => 'Zurückziehen',
    'widthdraw_done'                    => 'Der Änderungsantrag wurde zurückgezogen.',
    'title_amend_to'                    => 'Ändern in',
    'title_new'                         => 'Neuer Titel',
    'like_done'                         => 'Du stimmst diesem Änderungsantrag nun zu.',
    'dislike_done'                      => 'Du lehnst diesen Änderungsantrag nun ab.',
    'neutral_done'                      => 'Du stehst diesem Änderungsantrag wieder neutral gegenüber.',
    'support'                            => 'Unterstützen',
    'support_question'                   => 'Willst du den Änderungsantrag unterstützen?',
    'support_orga'                       => 'Organisation',
    'support_name'                       => 'Name',
    'support_done'                       => 'Du unterstützt diesen Änderungsantrag nun.',
    'support_already'                    => 'Du unterstützt diesen Änderungsantrag bereits',
    'support_collection_hint'            => 'Dieser Änderungsantrag ist noch nicht offiziell eingereicht. Nötig sind <strong>mindestens %MIN% Unterstützer*innen (aktueller Stand: %CURR%)</strong>. Wenn du ihn unterstützen willst, kannst du das unten auf dieser Seite tun.',
    'support_collection_reached_hint'    => 'Dieser Änderungsantrag ist noch nicht offiziell eingereicht. <strong>Die Mindestzahl an Unterstützer*innen (%MIN%) wurde erreicht</strong>, nun muss nur noch die/der Antragsteller*in die Einreichung bestätigen.',
    'support_reached_email_subject'      => 'Änderungsantrag: Unterstützer*innen-Anzahl erreicht',
    'support_reached_email_body'         => 'Hallo,<br><br>Dein Änderungsantrag "%TITLE%" hat die Mindestzahl an Unterstützer*innen erreicht. Damit kannst du ihn ab jetzt hier einreichen:<br><br><strong>%LINK%</strong><br><br>Bitte beachte, dass es <strong>zwingend notwendig</strong> ist, ihn nun explizit einzureichen.<br><br>Mit freundlichen Grüßen,<br>  Das Antragsgrün-Team',
    'support_finish_btn'                 => 'Änderungsantrag offiziell einreichen',
    'support_finish_err'                 => 'Das ist derzeit (noch) nicht möglich',
    'support_finish_done'                => 'Der Änderungsantrag ist nun offiziell eingereicht',
];
