<?php
return [
    'my_acc_title' => 'Mitt konto',
    'my_acc_bread' => 'Inställningar',
    'email_address' => 'E-postadress',
    'email_address_new' => 'Ny e-postadress',
    'email_blocklist' => 'Blockera alla e-postmeddelanden till detta konto',
    'email_unconfirmed' => 'obekräftad',
    'pwd_confirm' => 'Bekräfta lösenord',
    'pwd_change' => 'Byt lösenord',
    'pwd_change_hint' => 'Tomt = oförändrat',
    'organisation_primary' => 'Primär organisation',
    'name' => 'Namn',
    'name_given' => 'Förnamn',
    'name_family' => 'Efternamn',
    'organization' => 'Organisation',
    'user_group' => 'Användargrupp',
    'user_groups' => 'Användargrupper',
    'user_groups_con' => 'Detta evenemang',
    'user_groups_system' => 'Systemomfattande',
    '2fa_title' => 'Tvåfaktorsautentisering',
    '2fa_off' => 'Inte aktiv',
    '2fa_activate_opener' => 'Aktivera',
    '2fa_activated' => 'Konfigurerad',
    '2fa_remove_open' => 'Inaktivera tvåfaktorsautentisering',
    '2fa_remove_code' => 'Ange aktuell kod för att inaktivera',
    '2fa_add_explanation' => 'Du kan säkra ditt konto genom att lägga till en andra faktor utöver ditt lösenord.',
    '2fa_add_step1' => '1. Skanna QR-koden med appen',
    '2fa_add_step2' => '2. Ange den genererade koden',
    '2fa_img_alt' => 'QR-kod för TOTP; skanna denna kod med en app för tvåfaktorsautentisering efter eget val',
    '2fa_enter_code' => 'Ange kod',
    '2fa_register_title' => 'Konfigurera tvåfaktorsautentisering',
    '2fa_register_explanation' => 'Det krävs att du säkrar ditt konto med en andra faktor för att kunna använda det.',
    '2fa_general_explanation' => 'Använd en app som <a href="https://authy.com/download/">Authy</a>, <a href="https://freeotp.github.io">FreeOTP</a> (öppen källkod), <a href="https://www.microsoft.com/de-de/security/mobile-authenticator-app">Microsoft Authenticator</a> eller Google Authenticator.<br><br>Skanna QR-koden nedan, spara den på din telefon och ange sedan den genererade sifferkoden här längre ner på sidan.',
    '2fa_login_intro' => 'Du har säkrat ditt konto med en andra faktor. Öppna appen du använde för att konfigurera den och ange koden som visas i appen:',
    'force_pwd_title' => 'Byt lösenord',
    'force_pwd_explanation' => 'Ange ett nytt lösenord för att använda Antragsgrün.',
    'username_deleted' => 'Borttagen',
    'err_pwd_different' => 'De två lösenorden är inte identiska.',
    'err_pwd_length' => 'Lösenordet måste vara minst %MINLEN% tecken långt.',
    'err_pwd_fixed' => 'Lösenordet för detta konto kan inte ändras och därför inte heller återställas. Kontakta systemadministratören för hjälp.',
    'err_user_acode_notfound' => 'Användare hittades inte / ogiltig kod',
    'err_user_notfound' => 'Användarkontot %USER% hittades inte.',
    'err_code_wrong' => 'Den angivna koden är ogiltig.',
    'pwd_recovery_sent' => 'Ett e-postmeddelande för lösenordsåterställning har skickats.',
    'welcome' => 'Välkommen!',
    'err_email_acc_notfound' => 'Det finns inget konto med denna e-postadress...?',
    'err_email_acc_confirmed' => 'Detta konto är redan bekräftat.',
    'err_invalid_email' => 'Den angivna e-postadressen är ogiltig',
    'err_unknown' => 'Ett okänt fel inträffade',
    'err_unknown_ww_repeat' => 'Ett okänt fel inträffade.',
    'err_no_recovery' => 'Ingen återställningsbegäran har skickats under de senaste 24 timmarna.',
    'err_change_toolong' => 'Begäran är för gammal; vänligen gör en ny ändringsbegäran och bekräfta e-postmeddelandet inom 24 timmar',
    'err_2fa_nosession_user' => 'Ingen pågående TOTP-registrering hittades för den aktuella användaren',
    'err_2fa_nosession' => 'Ingen pågående inloggningssession',
    'err_2fa_timeout' => 'Bekräfta den andra faktorn inom %minutes% minuter.',
    'err_2fa_empty' => 'Tom kod angiven',
    'err_2fa_incorrect' => 'Felaktig kod angiven',
    'err_2fa_nocode' => 'Ingen andra faktor registrerad',
    'recover_mail_title' => 'Antragsgrün: Lösenordsåterställning',
    'recover_mail_body' => 'Hej!

Du har begärt en lösenordsåterställning. För att fortsätta, öppna följande sida och ange det nya lösenordet:

%URL%

Eller ange följande kod på återställningssidan: %CODE%',
    'err_recover_mail_sent' => 'Ett återställnings-e-postmeddelande har redan skickats under de senaste 24 timmarna.',
    'err_emailchange_mail_sent' => 'Du har redan begärt en e-postadressändring under de senaste 24 timmarna.',
    'err_emailchange_notfound' => 'Ingen e-postadressändring har begärts, eller så har den redan genomförts.',
    'err_emailchange_flood' => 'För att förhindra e-postöversvämning måste det vara minst 5 minuter mellan två e-postmeddelanden',
    'emailchange_mail_title' => 'Bekräfta ny e-postadress',
    'emailchange_mail_body' => 'Hej!

Du har begärt att ändra e-postadressen. För att fortsätta, öppna följande sida:

%URL%

',
    'emailchange_sent' => 'Ett bekräftelsemeddelande har skickats till denna adress. Öppna länken i meddelandet för att ändra adressen.',
    'emailchange_done' => 'E-postadressen har ändrats.',
    'emailchange_requested' => 'E-postadress begärd (ännu inte bekräftad)',
    'emailchange_call' => 'ändra',
    'emailchange_resend' => 'Nytt bekräftelsemeddelande',
    'email_pp_replyto' => 'Svara-till vid utskick av föreslaget förfarande (inställt av admin)',
    'del_title' => 'Ta bort konto',
    'del_explanation' => 'Här kan du ta bort detta konto. Du kommer inte längre att få några e-postmeddelanden, och det går inte att logga in efter detta.
        Din e-postadress, ditt namn och dina kontaktuppgifter kommer att tas bort.<br>
        Motioner och ändringsförslag som du har lämnat in kommer fortfarande att vara synliga. För att dra tillbaka redan inlämnade motioner, kontakta de ansvariga administratörerna för evenemanget.',
    'del_confirm' => 'Bekräfta borttagning',
    'del_do' => 'Ta bort',
    'noti_greeting' => 'Hej %NAME%,',
    'noti_bye' => 'Med vänliga hälsningar,
   Antragsgrün-teamet

--

Om du inte vill få fler e-postmeddelanden kan du avsluta prenumerationen här:
',
    'noti_new_motion_title' => '[Antragsgrün] Ny motion:',
    'noti_new_motion_body' => 'En ny motion har lämnats in:
Evenemang: %CONSULTATION%
Namn: %TITLE%
Motionär: %INITIATOR%
Länk: %LINK%',
    'noti_new_amend_title' => '[Antragsgrün] Nytt ändringsförslag till %TITLE%',
    'noti_new_amend_body' => 'Ett nytt ändringsförslag har lämnats in:
Evenemang: %CONSULTATION%
Motion: %TITLE%
Länk: %LINK%',
    'noti_amend_mymotion' => 'Ett nytt ändringsförslag har publicerats till din motion:
Evenemang: %CONSULTATION%
Motion: %TITLE%
Motionär: %INITIATOR%
Länk: %LINK%
%MERGE_HINT%',
    'noti_amend_mymotion_merge' => '
Om du håller med om detta ändringsförslag kan du överta ändringarna ("Överta ändringar i motionen" i sidofältet)',
    'noti_new_comment_title' => '[Antragsgrün] Ny kommentar till %TITLE%',
    'noti_new_comment_body' => '%TITLE% har fått en kommentar:
%LINK%',
    'acc_grant_email_title' => 'Antragsgrün-åtkomst',
    'acc_grant_email_userdata' => 'E-post/användarnamn: %EMAIL%
Lösenord: %PASSWORD%',
    'login_title' => 'Logga in',
    'login_con_pwd_title' => 'Logga in med evenemangets lösenord',
    'login_con_pwd' => 'Evenemangets lösenord',
    'login_username_title' => 'Logga in med användarnamn/lösenord',
    'login_create_account' => 'Skapa ett nytt konto',
    'login_username' => 'E-postadress/användarnamn',
    'login_email_placeholder' => 'Din e-postadress',
    'login_password' => 'Lösenord',
    'login_password_rep' => 'Lösenord (bekräfta)',
    'login_create_name' => 'Ditt namn',
    'login_captcha' => 'Ange koden som visas',
    'login_btn_login' => 'Logga in',
    'login_btn_create' => 'Skapa',
    'login_forgot_pw' => 'Glömt lösenordet?',
    'login_openid' => 'OpenID-inloggning',
    'login_openid_url' => 'OpenID-URL',
    'login_managed_hint' => '<strong>Tips:</strong> nya konton måste granskas av en administratör innan de får åtkomst till denna webbplats.',
    'managed_account_ask_btn' => 'Begär behörighet',
    'managed_account_asked' => 'Behörighet begärd.',
    'acc_request_noti_subject' => 'Begäran: Åtkomst till webbplatsen',
    'acc_request_noti_body' => 'Användaren %USERNAME% (%EMAIL%) begär åtkomst till webbplatsen "%CONSULTATION%". På följande sida kan du bevilja åtkomsten: %ACTIONLINK%',
    'login_confirm_registration' => 'Bekräfta registreringen',
    'login_err_password' => 'Ogiltigt lösenord.',
    'login_err_username' => 'Användarnamnet hittades inte.',
    'login_err_siteaccess' => 'Detta konto är inte behörigt att logga in på denna webbplats.',
    'login_err_captcha' => 'Den angivna koden matchade inte bilden',
    'login_err_nocaptcha' => 'Ange koden som visas på bilden.',
    'create_err_emailexists' => 'Denna e-postadress är redan registrerad på ett annat konto',
    'create_err_siteaccess' => 'Det går inte att skapa konton för denna webbplats.',
    'create_err_emailinvalid' => 'Ange en giltig e-postadress.',
    'create_err_pwdlength' => 'Lösenordet måste vara minst %MINLEN% tecken långt.',
    'create_err_pwdmismatch' => 'De två angivna lösenorden matchar inte.',
    'create_err_noname' => 'Ange ditt namn.',
    'err_contact_required' => 'Du måste ange en kontaktadress.',
    'create_emailconfirm_title' => 'Registrering hos Antragsgrün / motion.tools',
    'create_emailconfirm_msg' => 'Hej,

klicka på följande länk för att bekräfta ditt konto:
%BEST_LINK%

...eller ange följande kod på webbplatsen: %CODE%

Med vänliga hälsningar,
	Antragsgrün-teamet',
    'access_denied_title' => 'Ingen åtkomst',
    'access_denied_body' => 'Du har inte åtkomst till denna webbplats.',
    'access_granted_email' => 'Hej,

Du har precis fått åtkomst till: %LINK%

Med vänliga hälsningar,
	Antragsgrün-teamet',
    'confirm_title' => 'Bekräfta ditt konto',
    'confirm_username' => 'E-postadress/användarnamn',
    'confirm_mail_sent' => 'Ett e-postmeddelande har just skickats till din adress. Bekräfta att du har mottagit meddelandet genom att klicka på länken i det eller genom att ange den angivna koden på denna sida.',
    'confirm_code' => 'Bekräftelsekod',
    'confirm_btn_do' => 'Bekräfta',
    'confirm_resend' => 'Skicka bekräftelsemeddelandet igen',
    'confirmed_title' => 'Konto bekräftat',
    'confirmed_msg' => 'Klart! Ditt konto är bekräftat och du kan sätta igång.',
    'confirmed_screening_msg' => 'Ditt konto är nu giltigt. Administratören har meddelats för att bevilja dig åtkomst till denna webbplats.',
    'recover_title' => 'Lösenordsåterställning',
    'recover_step1' => '1. Ange din e-postadress',
    'recover_email_place' => 'min@epostadress.se',
    'recover_send_email' => 'Skicka bekräftelsemeddelande',
    'recover_step2' => '2. Ange ett nytt lösenord',
    'recover_email' => 'E-postadress',
    'recover_code' => 'Bekräftelsekod',
    'recover_new_pwd' => 'Nytt lösenord',
    'recover_set_pwd' => 'Ange nytt lösenord',
    'recovered_title' => 'Nytt lösenord angivet',
    'recovered_msg' => 'Ditt lösenord har ändrats.',
    'deleted_title' => 'Konto borttaget',
    'deleted_msg' => 'Ditt konto har tagits bort.',
    'no_noti_title' => 'Avsluta prenumeration på aviseringar',
    'no_noti_bc' => 'Aviseringar',
    'no_noti_unchanged' => 'Lämna aviseringarna som de är',
    'no_noti_consultation' => 'Avsluta prenumeration på aviseringar för detta evenemang (%NAME%)',
    'no_noti_all' => 'Avsluta prenumeration på alla aviseringar',
    'no_noti_blocklist' => 'Inga e-postmeddelanden alls <small>(inklusive e-post för lösenordsåterställning m.m.)</small>',
    'no_noti_save' => 'Spara',
    'notification_title' => 'E-postaviseringar',
    'notification_intro' => 'Du kan välja individuellt för varje evenemang vad du vill bli aviserad om:',
    'export_title' => 'Dataexport',
    'export_intro' => 'Du kan ladda ner alla personuppgifter som sparats om dig i Antragsgrün, i ett maskinläsbart JSON-format.',
    'export_btn' => 'Ladda ner',
    'group_template_siteadmin' => 'Webbplatsadmin',
    'group_template_siteadmin_h' => 'Alla rättigheter för alla evenemang på denna webbplats/subdomän.',
    'group_template_consultationadmin' => 'Evenemangsadmin',
    'group_template_consultationadmin_h' => 'Alla rättigheter för detta evenemang.',
    'group_template_proposed' => 'Föreslaget förfarande',
    'group_template_proposed_h' => 'Kan redigera det föreslagna förfarandet, men inte motionerna och ändringsförslagen själva.',
    'group_template_progress' => 'Statusrapporter',
    'group_template_progress_h' => 'Kan redigera statusrapporter för beslut, men inte besluten själva.',
    'group_template_participant' => 'Deltagare',
    'group_template_participant_h' => 'Inga särskilda rättigheter. Endast relevant om åtkomsten till denna webbplats är begränsad.',
    'pw_x_chars' => 'Lösenordet måste vara minst %NUM% tecken långt.',
    'pw_min_x_chars' => 'Min. %NUM% tecken',
    'pw_no_match' => 'Lösenorden matchar inte.',
];
