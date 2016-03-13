<?php
return [
    'my_acc_title'              => 'My Account',
    'my_acc_bread'              => 'Settings',
    'email_address'             => 'E-Mail-Address',
    'email_address_new'         => 'New E-Mail-Adresse',
    'email_blacklist'           => 'Block all e-mails to this account',
    'email_unconfirmed'         => 'unconfirmed',
    'pwd_confirm'               => 'Confirm Password',
    'pwd_change'                => 'Change Password',
    'pwd_change_hint'           => 'Empty = leave unchanged',
    'name'                      => 'Name',
    'err_pwd_different'         => 'The two passwords are not equal.',
    'err_pwd_length'            => 'The password has to be at least %MINLEN% characters long.',
    'err_user_acode_notfound'   => 'User not found / invalid code',
    'err_user_notfound'         => 'The user account %USER% was not found.',
    'err_code_wrong'            => 'The given code is invalid.',
    'pwd_recovery_sent'         => 'A password-recovery-mail has been sent.',
    'welcome'                   => 'Welcome!',
    'err_email_acc_notfound'    => 'There is not account with this e-mail-address...?',
    'err_invalid_email'         => 'The given e-mail-address is invalid',
    'err_unknown'               => 'An unknown error occurred',
    'err_unknown_ww_repeat'     => 'An unknown error occurred.',
    'err_no_recovery'           => 'No recovery-request was sent within the last 24 hours.',
    'err_change_toolong'        => 'The request is too old; please request another change request and confirm the e-mail withing 24 hours',
    'recover_mail_title'        => 'Antragsgrün: Password-Recovery',
    'recover_mail_body'         => "Hi!\n\nYou requested a password recovery. " .
        "To proceed, please open the following page and enter the new password there:\n\n%URL%\n\n" .
        "Or enter the following code on the recovery-page: %CODE%",
    'err_recover_mail_sent'     => 'There already has been a recovery-e-mail within the last 24 hours.',
    'err_emailchange_mail_sent' => 'You already requested an e-mail-change within the last 24 hours.',
    'err_emailchange_notfound'  => 'Diese E-Mail-Änderung wurde nicht beantragt oder bereits durchgeführt.',
    'err_emailchange_flood'     => 'To prevent e-mail-flooding, there needs to be a gap of at least 5 minutes between sending two e-mails',
    'emailchange_mail_title'    => 'Confirm new e-mail-address',
    'emailchange_mail_body'     => "Hi!\n\nYou requested to change the e-mail-address. " .
        "To proceed, please open the following page:\n\n%URL%\n\n",
    'emailchange_sent'          => 'A confirmation-e-mail has been sent to this address. ' .
        'Please open the link in it to change the address.',
    'emailchange_done'          => 'The e-mail-address has been changed.',
    'emailchange_requested'     => 'E-Mail-address requested (not confirmed yet)',
    'emailchange_call'          => 'change',
    'emailchange_resend'        => 'New confirmation mail',
    'del_title'                 => 'Delete account',
    /*
    'del_explanation'           => 'Hier kannst du diesen Zugang von Antragsgrün löschen. Du erhältst keine E-Mail-Benachrichtigungen mehr,
        ein Login ist auch nicht mehr möglich. Deine E-Mail-Adresse, Name, Passwort usw. werden damit aus unserem
        System gelöscht.<br>
        Eingebrachte (Änderungs-)Anträge bleiben aber erhalten. Um eingebrachte Anträge zu entfernen,
        wende dich bitte an die Administrator*innen der jeweiligen Unterseite.',
    'del_confirm'               => 'Löschen bestätigen',
    'del_do'                    => 'Löschen',
    'noti_greeting'             => 'Hallo %NAME%,',
    'noti_bye'                  => "Liebe Grüße,\n   Das Antragsgrün-Team\n\n--\n\n" .
        "Falls du diese Benachrichtigung abbestellen willst, kannst du das hier tun:\n",
    'noti_new_motion_title'     => '[Antragsgrün] Neuer Antrag:',
    'noti_new_motion_body'      => "Es wurde ein neuer Antrag eingereicht:\nAnlass: %CONSULTATION%\n" .
        "Name: %TITLE%\nLink: %LINK%",
    'noti_new_amend_title'      => '[Antragsgrün] Neuer Änderungsantrag zu %TITLE%',
    'noti_new_amend_body'       => "Es wurde ein neuer Änderungsantrag eingereicht:\nAnlass: %CONSULTATION%\n" .
        "Antrag: %TITLE%\nLink: %LINK%",
    'noti_new_comment_title'    => '[Antragsgrün] Neuer Kommentar zu %TITLE%',
    'noti_new_comment_body'     => "Es wurde ein neuer Kommentar zu %TITLE% geschrieben:\n%LINK%",
    'acc_grant_email_title'     => 'Antragsgrün-Zugriff',
    'acc_grant_email_userdata' => "E-Mail / Benutzer*innenname: %EMAIL%\nPasswort: %PASSWORD%",
    */


    'login_title'             => 'Login',
    'login_username_title'    => 'Login using username/password',
    'login_create_account'    => 'Create a new account',
    'login_username'          => 'E-Mail-Address / Username',
    'login_email_placeholder' => 'Your email-address',
    'login_password'          => 'Password',
    'login_password_rep'      => 'Password (Confirm)',
    'login_create_name'       => 'Your name',
    'login_btn_login'         => 'Log In',
    'login_btn_create'        => 'Create',
    'login_forgot_pw'         => 'Forgot your password?',
    'login_openid'            => 'OpenID-Login',
    'login_openid_url'        => 'OpenID-URL',

    'access_denied_title' => 'No access',
    'access_denied_body'  => 'You don\' have access to this site. If you think this is an error, please contact the site administrator (as stated in the imprint).',
];
