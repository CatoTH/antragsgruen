<?php
return [
    'err_unknown_ww_repeat'     => 'Es trat ein unbekannter Fehler auf.' . "\n" .
        'Falls du versucht hast, dich mit deinen SPD-Account-Zugangsdaten einzuloggen, ' .
        'versuch es einfach noch ein zweites Mal - möglicherweise war das nur ' .
        'ein temporärer Fehler seitens des SAML.',
    'recover_mail_title'        => 'Antragstool SPD TH: Passwort-Wiederherstellung',
    'recover_mail_body'         => "Hallo!\n\nDu hast eine Passwort-Wiederherstellung angefordert. " .
        "Um diese durchzuführen, rufe bitte folgenden Link auf und gib dort das neue Passwort ein:\n\n%URL%\n\n" .
        "Oder gib in dem Wiederherstellungs-Formular folgenden Code ein: %CODE%",
    'emailchange_mail_title'    => 'Neue E-Mail-Adresse bestätigen',
    'emailchange_mail_body'     => "Hallo!\n\nDu hast eine E-Mail-Änderung beantragt. " .
        "Um diese durchzuführen, rufe bitte folgenden Link auf:\n\n%URL%\n\n",
    'emailchange_sent'          => 'Es wurde eine Bestätigungs-E-Mail an die angegebene Adresse geschickt. ' .
        'Bitte öffne den Link darin, um die neue E-Mail-Adresse zu aktivieren.',
    'emailchange_done'          => 'Die E-Mail-Adresse wurde wie gewünscht geändert.',
    'emailchange_requested'     => 'E-Mail-Adresse beantragt (noch nicht bestätigt)',
    'emailchange_call'          => 'ändern',
    'emailchange_resend'        => 'Neue Bestätigungs-Mail',
    'email_pp_replyto'          => 'Reply-To bei Verfahrensvorschlägen (vom Admin eingestellt)',
    'del_title'                 => 'Zugang löschen',
    'del_explanation'           => 'Hier kannst du diesen Zugang vom Antragstool der SPD Thüringen löschen. Du erhältst keine E-Mail-Benachrichtigungen mehr,
        ein Login ist auch nicht mehr möglich. Deine E-Mail-Adresse, Name, Passwort usw. werden damit aus unserem
        System gelöscht.<br>
        Eingebrachte (Änderungs-)Anträge bleiben aber erhalten. Um eingebrachte Anträge zu entfernen,
        wende dich bitte an die Administrator*innen der jeweiligen Unterseite.',
    'del_confirm'               => 'Löschen bestätigen',
    'del_do'                    => 'Löschen',
    'noti_greeting'             => 'Hallo %NAME%,',
    'noti_bye'                  => "Solidarische Grüße,\n   SPD Thüringen\n\n--\n\n" .
        "Falls du diese Benachrichtigung abbestellen willst, kannst du das hier tun:\n",
    'noti_new_motion_title'     => '[SPD-Thüringen] Neuer Antrag:',
    'noti_new_motion_body'      => "Es wurde ein neuer Antrag eingereicht:\nAnlass: %CONSULTATION%\n" .
        "Name: %TITLE%\nAntragsteller*in: %INITIATOR%\nLink: %LINK%",
    'noti_new_amend_title'      => '[SPD-Thüringen] Neuer Änderungsantrag zu %TITLE%',
    'noti_new_amend_body'       => "Es wurde ein neuer Änderungsantrag eingereicht:\nAnlass: %CONSULTATION%\n" .
        "Antrag: %TITLE%\nLink: %LINK%",
    'noti_amend_mymotion'       => "Es wurde ein neuer Änderungsantrag zu deinem Antrag eingereicht:\nAnlass: %CONSULTATION%\n" .
        "Antrag: %TITLE%\nAntragsteller*in: %INITIATOR%\nLink: %LINK%\n%MERGE_HINT%",
    'noti_amend_mymotion_merge' => "\nWenn du diesem Änderungsantrag zustimmst, kannst du ihn in deinen Antrag übernehmen (\"In den Antrag übernehmen\" in der Sidebar)",
    'noti_new_comment_title'    => '[SPD-Thüringen] Neuer Kommentar zu %TITLE%',
    'noti_new_comment_body'     => "Es wurde ein neuer Kommentar zu %TITLE% geschrieben:\n%LINK%",
    'acc_grant_email_title'     => 'SPD-Antragstool-Zugriff',
    'acc_grant_email_userdata'  => "E-Mail / Benutzer*innenname: %EMAIL%\nPasswort: %PASSWORD%",

    'login_title'             => 'Login',
    'login_con_pwd_title'     => 'Login mit Veranstaltungs-Passwort',
    'login_con_pwd'           => 'Veranstaltungs-Passwort',
    'login_username_title'    => 'Login per Benutzer*innenname / Passwort',
    'login_create_account'    => 'Neuen Zugang anlegen',
    'login_username'          => 'E-Mail-Adresse / Benutzer*innenname',
    'login_email_placeholder' => 'E-Mail-Adresse',
    'login_password'          => 'Passwort',
    'login_password_rep'      => 'Passwort (Bestätigung)',
    'login_create_name'       => 'Dein Name',
    'login_btn_login'         => 'Einloggen',
    'login_btn_create'        => 'Anlegen',
    'login_forgot_pw'         => 'Passwort vergessen?',
    'login_openid'            => 'OpenID-Login',
    'login_openid_url'        => 'OpenID-URL',
    'login_managed_hint'      => '<strong>Hinweis:</strong> neu angelegte Zugänge müssen zunächst von einer Administratorin bzw. einem Administratoren freigeschaltet werden.',

    'acc_request_noti_subject' => 'Anfrage: Zugriff auf SPD-Antragstool-Seite',
    'acc_request_noti_body'    => 'Die Benutzerin / der Benutzer %USERNAME% (%EMAIL%) fragt an, Zugriff auf die Seite "%CONSULTATION%" zu bekommen. Auf folgender Seite kannst du die Anfrage ggf. bestätigen: %ACTIONLINK%',

    'login_confirm_registration' => 'Ich stimme der elektronischen Verarbeitung meiner freiwilligen Angaben durch das SPD-Antragstool zu. Meine Angaben zu Name und E-Mail-Adresse dürfen dazu verwendet werden, mir für mich passende Informationen zukommen zu lassen. Ich kann diese Zustimmung jederzeit per Mail an thueringen@spd.de oder per Schreiben an die im Impressum angegebene Adresse widerrufen.',

    'create_emailconfirm_title' => 'Anmeldung beim SPD-Antragstool',
    'create_emailconfirm_msg'   =>
        "Hallo,\n\num deinen Antragstool-Zugang zu aktivieren, klicke entweder auf folgenden Link:\n" .
        "%BEST_LINK%\n\n"
        . "...oder gib, wenn du auf der Antragstool-Seite danach gefragt wirst, folgenden Code ein: %CODE%\n\n"
        . "Solidarische Grüße,\n\tSPD Thüringen.",

    'access_denied_title'  => 'Kein Zugriff',
    'access_denied_body'   => 'Dein Zugang ist für diese Seite nicht freigeschaltet.',
    'access_granted_email' => "Hallo,\n\ndu hast soeben Zugriff auf diese Veranstaltung bekommen:\n%LINK%\n\n"
        . "Solidarische Grüße,\n\tSPD Thüringen.",

    'confirm_title'     => 'Zugang bestätigen',
    'confirm_username'  => 'E-Mail-Adresse / Benutzer*innenname',
    'confirm_mail_sent' => 'Dir wurde eben eine E-Mail an die angegebene Adresse geschickt.
                            Bitte bestätige den Empfang dieser E-Mail, indem du den Link darin aufrufst oder
                            hier den Code in der E-Mail eingibst.',
    'confirm_code'      => 'Bestätigungs-Code',
    'confirm_btn_do'    => 'Bestätigen',

    'confirmed_title'         => 'Zugang bestätigt',
    'confirmed_msg'           => 'Alles klar! Dein Zugang ist freigeschaltet und du kannst loslegen!',
    'confirmed_screening_msg' => 'Dein Zugang ist nun eingerichtet. Der Admin der Veranstaltung wurde benachrichtigt, ihn für diese Veranstaltung freizuschalten.',

    'no_noti_all'          => 'Alle SPD-Antragstool-Benachrichtigungen abbestellen',

    'export_intro' => 'Hier kannst du alle personenbezogene Daten, die im SPD-Antragstool über dich gespeichert sind, in einem maschinenlesbaren JSON-Format herunterladen.',
];
