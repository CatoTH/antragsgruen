<?php
return [
    'my_acc_title'              => 'My account',
    'my_acc_bread'              => 'Settings',
    'email_address'             => 'E-mail address',
    'email_address_new'         => 'New e-mail addresse',
    'email_blacklist'           => 'Block all e-mails to this account',
    'email_unconfirmed'         => 'unconfirmed',
    'pwd_confirm'               => 'Confirm password',
    'pwd_change'                => 'Change password',
    'pwd_change_hint'           => 'Empty = leave unchanged',
    'name'                      => 'Name',
    'err_pwd_different'         => 'The two passwords are not the same.',
    'err_pwd_length'            => 'The password has to be at least %MINLEN% characters long.',
    'err_user_acode_notfound'   => 'User not found / invalid code',
    'err_user_notfound'         => 'The user account %USER% was not found.',
    'err_code_wrong'            => 'The given code is invalid.',
    'pwd_recovery_sent'         => 'A password recovery e-mail has been sent.',
    'welcome'                   => 'Welcome!',
    'err_email_acc_notfound'    => 'There is not account with this e-mail address...?',
    'err_invalid_email'         => 'The given e-mail address is invalid',
    'err_unknown'               => 'An unknown error occurred',
    'err_unknown_ww_repeat'     => 'An unknown error occurred.',
    'err_no_recovery'           => 'No recovery request was sent within the last 24 hours.',
    'err_change_toolong'        => 'The request is too old; please request another change request and confirm the e-mail within 24 hours',
    'recover_mail_title'        => 'Antragsgrün: Password recovery',
    'recover_mail_body'         => "Hi!\n\nYou requested a password recovery. " .
        "To proceed, please open the following page and enter the new password:\n\n%URL%\n\n" .
        "Or enter the following code on the recovery page: %CODE%",
    'err_recover_mail_sent'     => 'There already has been a recovery e-mail sent within the last 24 hours.',
    'err_emailchange_mail_sent' => 'You already requested an e-mail change within the last 24 hours.',
    'err_emailchange_notfound'  => 'No e-mail change was requested or it is already being implemented.',
    'err_emailchange_flood'     => 'To prevent e-mail flooding, there needs to be a gap of at least 5 minutes between sending two e-mails',
    'emailchange_mail_title'    => 'Confirm new e-mail address',
    'emailchange_mail_body'     => "Hi!\n\nYou requested to change the e-mail address. " .
        "To proceed, please open the following page:\n\n%URL%\n\n",
    'emailchange_sent'          => 'A confirmation e-mail has been sent to this address. ' .
        'Please open the included link to change the address.',
    'emailchange_done'          => 'The e-mail address has been changed.',
    'emailchange_requested'     => 'E-mail address requested (not confirmed yet)',
    'emailchange_call'          => 'change',
    'emailchange_resend'        => 'New confirmation mail',
    'del_title'                 => 'Delete account',
    'del_explanation'           => 'Here you can delete this account. You will not receive any more e-mails, no login will be possible after this.
        Your e-mail address, name and contact data will be deleted.<br>
        Motions and amendments you submitted will remain visible. To withdraw already submitted motions, please contact the relevant convention administrators.',
    'del_confirm'               => 'Confirm delete',
    'del_do'                    => 'Delete',
    'noti_greeting'             => 'Hi %NAME%,',
    'noti_bye'                  => "Kind regards,\n   The Antragsgrün Team\n\n--\n\n" .
        "If you do not want to receive any more e-mails, you can unsubscribe here:\n",
    'noti_new_motion_title'     => '[Antragsgrün] New motion:',
    'noti_new_motion_body'      => "A new motion was submitted:\nConsultation: %CONSULTATION%\n" .
        "Name: %TITLE%\nLink: %LINK%",
    'noti_new_amend_title'      => '[Antragsgrün] New amendment for %TITLE%',
    'noti_new_amend_body'       => "A new amendment was submitted:\nConsultation: %CONSULTATION%\n" .
        "Motion: %TITLE%\nLink: %LINK%",
    'noti_amend_mymotion'       => "A new amendment has been published to your motion:\nConsultation: %CONSULTATION%\n" .
        "Motion: %TITLE%\nLink: %LINK%\n%MERGE_HINT%",
    'noti_amend_mymotion_merge' => "\nIf you agree with this amendment, you can adopt the changes (\"Adopt changes into motion\" in the sidebar)",
    'noti_new_comment_title'    => '[Antragsgrün] New comment to %TITLE%',
    'noti_new_comment_body'     => "%TITLE% was commented:\n%LINK%",
    'acc_grant_email_title'     => 'Antragsgrün access',
    'acc_grant_email_userdata'  => "E-mail / username: %EMAIL%\nPassword: %PASSWORD%",


    'login_title'             => 'Login',
    'login_username_title'    => 'Login using username/password',
    'login_create_account'    => 'Create a new account',
    'login_username'          => 'E-mail address / username',
    'login_email_placeholder' => 'Your e-mail address',
    'login_password'          => 'Password',
    'login_password_rep'      => 'Password (Confirm)',
    'login_create_name'       => 'Your name',
    'login_btn_login'         => 'Log In',
    'login_btn_create'        => 'Create',
    'login_forgot_pw'         => 'Forgot your password?',
    'login_openid'            => 'OpenID login',
    'login_openid_url'        => 'OpenID URL',

    'login_err_password'      => 'Invalid password.',
    'login_err_username'      => 'Username not found.',
    'login_err_siteaccess'    => 'This account is not eligible to log in to this site.',
    'create_err_emailexists'  => 'This e-mail-address is already registered to another account',
    'create_err_siteaccess'   => 'Creating accounts is not allowed for this site.',
    'create_err_emailinvalid' => 'Please enter a valid e-mailaddress.',
    'create_err_pwdlength'    => 'The password needs to be at least %MINLEN% characters long.',
    'create_err_pwdmismatch'  => 'The two passwords entered do not match.',
    'create_err_noname'       => 'Please enter your name.',
    'err_contact_required'    => 'You need to enter a contact address.',

    'create_emailconfirm_title' => 'Registration at Antragsgrün / motion.tools',
    'create_emailconfirm_msg'   =>
        "Hi,\n\nplease click on the following link to confirm your account:\n" .
        "%BEST_LINK%\n\n"
        . "...or enter the following code on the site: %CODE%\n\n"
        . "With kind regards,\n\tTeam Antragsgrün",

    'access_denied_title' => 'No access',
    'access_denied_body'  => 'You don\' have access to this site. If you think this is an error, please contact the site administrator (as stated in the imprint).',

    'confirm_title'     => 'Confirm your account',
    'confirm_username'  => 'E-mail-address / username',
    'confirm_mail_sent' => 'An email was just sent to your address. Plase confirm receiving this mail by clicking on the link in the mail of by entering the given code on this page.',
    'confirm_code'      => 'Confirmation code',
    'confirm_btn_do'    => 'Confirm',

    'confirmed_title' => 'Account confirmed',
    'confirmed_msg'   => 'Your\'re all set! Your account is confirmed and you are good to go.',

    'recover_title'       => 'Password recovery',
    'recover_step1'       => '1. Enter your e-mail-address',
    'recover_email_place' => 'my@email-address.org',
    'recover_send_email'  => 'Send confirmation-e-mail',
    'recover_step2'       => '2. Set a new password',
    'recover_email'       => 'E-mail-address',
    'recover_code'        => 'Confirmation code',
    'recover_new_pwd'     => 'New password',
    'recover_set_pwd'     => 'Set new password',

    'recovered_title' => 'New password set',
    'recovered_msg'   => 'Your password has been changed.',

    'deleted_title' => 'Account deleted',
    'deleted_msg'   => 'Your account has been deleted.',

    'no_noti_title'        => 'Unsubscribe from notifications',
    'no_noti_bc'           => 'Notifications',
    'no_noti_unchanged'    => 'Leave the notifications as they are',
    'no_noti_consultation' => 'Unsubscribe from notifications of this consultation (%NAME%)',
    'no_noti_all'          => 'Unsubscribe from all notifications',
    'no_noti_blacklist'    => 'No e-mails at all <small>(including password-recovery-emails etc.)</small>',
    'no_noti_save'         => 'Save',
];
