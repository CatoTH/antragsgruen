<?php
return [
    'my_acc_title'              => 'Mein Zugang',
    'my_acc_bread'              => 'Einstellungen',
    'email_address'             => 'E-Mail-Adresse',
    'email_address_new'         => 'Neue E-Mail-Adresse',
    'email_blacklist'           => 'Jeglichen Mail-Versand an diese Adresse unterbinden',
    'email_unconfirmed'         => 'unbestätigt',
    'pwd_confirm'               => 'Passwort bestätigen',
    'pwd_change'                => 'Passwort ändern',
    'pwd_change_hint'           => 'Leer lassen, falls unverändert',
    'name'                      => 'Name',
    'err_pwd_different'         => 'Die beiden Passwörter stimmen nicht überein.',
    'err_pwd_length'            => 'Das Passwort muss mindestens %MINLEN% Zeichen lang sein.',
    'err_user_acode_notfound'   => 'BenutzerIn nicht gefunden / Ungültiger Code',
    'err_user_notfound'         => 'Der Account %USER% wurde nicht gefunden.',
    'err_code_wrong'            => 'Der angegebene Code stimmt leider nicht.',
    'pwd_recovery_sent'         => 'Dir wurde eine Passwort-Wiederherstellungs-Mail geschickt.',
    'welcome'                   => 'Willkommen!',
    'err_email_acc_notfound'    => 'Es existiert kein Zugang mit der angegebenen E-Mail-Adresse...?',
    'err_invalid_email'         => 'Die angegebene E-Mail-Adresse enthält einen Fehler',
    'err_unknown'               => 'Es trat leider ein unvorhergesehener Fehler auf',
    'err_unknown_ww_repeat'     => 'Es trat ein unbekannter Fehler auf.' . "\n" .
        'Falls du versucht hast, dich mit deinen Wurzelwerk-Zugangsdaten einzuloggen, ' .
        'versuch es einfach noch ein zweites Mal - möglicherweise war das nur ' .
        'ein temporärer Fehler seitens des Wurzelwerks.',
    'err_no_recovery'           => 'Es wurde kein Wiederherstellungs-Antrag innerhalb der letzten 24 Stunden gestellt.',
    'err_change_toolong'        => 'Die Änderungsanfrage ist schon zu lange her; ' .
        'bitte fordere eine neue Änderung an und rufe den Link innerhalb von 24 Stunden auf',
    'recover_mail_title'        => 'Antragsgrün: Passwort-Wiederherstellung',
    'recover_mail_body'         => "Hallo!\n\nDu hast eine Passwort-Wiederherstellung angefordert. " .
        "Um diese durchzuführen, rufe bitte folgenden Link auf und gib dort das neue Passwort ein:\n\n%URL%\n\n" .
        "Oder gib in dem Wiederherstellungs-Formular folgenden Code ein: %CODE%",
    'err_recover_mail_sent'     => 'Es wurde bereits eine Wiederherstellungs-E-Mail in den letzten 24 Stunden verschickt.',
    'err_emailchange_mail_sent' => 'Es wurde bereits eine E-Mail-Änderung in den letzten 24 Stunden beantragt.',
    'err_emailchange_notfound'  => 'Diese E-Mail-Änderung wurde nicht beantragt oder bereits durchgeführt.',
    'err_emailchange_flood'     => 'Zwischen zwei E-Mails müssen mindestens 5 Minuten liegen, um versehentliches E-Mail-Flooding zu verhindern',
    'emailchange_mail_title'    => 'Neue E-Mail-Adresse bestätigen',
    'emailchange_mail_body'     => "Hallo!\n\nDu hast eine E-Mail-Änderung beantragt. " .
        "Um diese durchzuführen, rufe bitte folgenden Link auf:\n\n%URL%\n\n",
    'emailchange_sent'          => 'Es wurde eine Bestätigungs-E-Mail an die angegebene Adresse geschickt. ' .
        'Bitte öffne den Link darin, um die neue E-Mail-Adresse zu aktivieren.',
    'emailchange_done'          => 'Die E-Mail-Adresse wurde wie gewünscht geändert.',
    'emailchange_requested'     => 'E-Mail-Adresse beantragt (noch nicht bestätigt)',
    'emailchange_call'          => 'ändern',
    'emailchange_resend'        => 'Neue Bestätigungs-Mail',
    'del_title'                 => 'Zugang löschen',
    'del_explanation'           => 'Hier kannst du diesen Zugang von Antragsgrün löschen. Du erhältst keine E-Mail-Benachrichtigungen mehr,
        ein Login ist auch nicht mehr möglich. Deine E-Mail-Adresse, Name, Passwort usw. werden damit aus unserem
        System gelöscht.<br>
        Eingebrachte (Änderungs-)Anträge bleiben aber erhalten. Um eingebrachte Anträge zu entfernen,
        wende dich bitte an die AdministratorInnen der jeweiligen Unterseite.',
    'del_confirm'               => 'Löschen bestätigen',
    'del_do'                    => 'Löschen',
];
