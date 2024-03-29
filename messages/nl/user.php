<?php
return [
    'my_acc_title'              => 'Mijn account',
    'my_acc_bread'              => 'Instellingen',
    'email_address'             => 'E-mailadres',
    'email_address_new'         => 'Nieuw e-mailadres',
    'email_blocklist'           => 'Blokker alle e-mails naar deze account',
    'email_unconfirmed'         => 'unconfirmed',
    'pwd_confirm'               => 'Bevestig wachtword',
    'pwd_change'                => 'Verander wachtword',
    'pwd_change_hint'           => 'Leeg = verander niet',
    'organisation_primary'      => 'Primaire organisatie',
    'name'                      => 'Naam',
    'name_given'                => 'Voornaam',
    'name_family'               => 'Familie naam',
    'user_group'                => 'Gebruikers groep',
    'user_groups'               => 'Gebruikers groepen',
    'user_groups_con'           => 'Deze site',
    'user_groups_system'        => 'Systeem-wijd',
    'username_deleted'          => 'Verwijdert',
    'err_pwd_different'         => 'De wachtwoorden zijn niet gelijk.',
    'err_pwd_length'            => 'Het wachtwoord ,moet tenminste %MINLEN% karakters bevatten.',
    'err_user_acode_notfound'   => 'Gebruiker onbekend, ongeldig',
    'err_user_notfound'         => 'Gebruikersnaam %USER% is niet bekend.',
    'err_code_wrong'            => 'De ingevoerde code is onjuist.',
    'pwd_recovery_sent'         => 'Een e-mail voor herstel van het wachtwoord is verstuurd.',
    'welcome'                   => 'Welkom!',
    'err_email_acc_notfound'    => 'Er is geen gebruiker met dit e-mail address...?',
    'err_email_acc_confirmed'   => 'Deze gebruiker bestaat al.',
    'err_invalid_email'         => 'Het opgegeven e-mail adres is onjuist',
    'err_unknown'               => 'Een onbekende fout is opgetreden,',
    'err_unknown_ww_repeat'     => 'Een onbekende fout is opgetreden.',
    'err_no_recovery'           => 'De afgelopen 24 uur is geen herstel opdracht verstuurd.',
    'err_change_toolong'        => 'Het verzoek is verlopen; dien aub een nieuw verzoek in EN bevestig je e-mail adres binnen 24 uur.',
    'recover_mail_title'        => 'Motion Tools: Herstel wachtwoord',
    'recover_mail_body'         => "Hi!\n\nJe hebt gevraagd om herstel van je wachtwoord. " .
        "Om verder te gaan, open de volgende pagina en voer je nieuwe wachtwoord in:\n\n%URL%\n\n" .
        "Of voer de volgende code in op de herstel pagina: %CODE%",
    'err_recover_mail_sent'     => 'Er is reeds een herstel e-mail verstuurd in de afgelopen 24 uur.',
    'err_emailchange_mail_sent' => 'Je hebt de afgelopen 24 uur al verzocht om verandering van je e-mail adres.',
    'err_emailchange_notfound'  => 'Verandering van e-mail adres is reeds gevraagd OF het wordt reeds uitgevoerd.',
    'err_emailchange_flood'     => 'Om teveel e-amils te vermijden, dient er tenminste 5 minuten wachttijd te zijn na het versturen van een eerdere mail.',
    'emailchange_mail_title'    => 'Bevestig je NIEUWE e-mail adres',
    'emailchange_mail_body'     => "Hi!\n\nJe hebt verozht om verandering van je e-mail adres. " .
        "Om verder te gaan, open aub de volgende pagina:\n\n%URL%\n\n",
    'emailchange_sent'          => 'Een bevestiging is verstuurd naar het volgende adres. ' .
        'Open deze link om het adres te wijzigen.',
    'emailchange_done'          => 'Het e-mail adres is gewijzigd.',
    'emailchange_requested'     => 'Verandering van e-mail is aangevraagd (maar nog niet bevestigd)',
    'emailchange_call'          => 'wijzig',
    'emailchange_resend'        => 'New confirmation mail',
    'email_pp_replyto'          => 'Reply-To voor het versturen van een voorstel voor nieuwe procedures (wordt doorgevoerd door de administratie)',
    'del_title'                 => 'Verwijder account',
    'del_explanation'           => 'Hier kun je deze account verwijderen. Je ontvangt geen e-mails meer. Je kunt niet meer inloggen.
        Je e-mail adres, gebruikersnaam en contactgegevens zullen worden verwijdert.<br>
        Moites en amendementen die je hebt ingediend blijven zichtbaar. Om reeds ingediende moties terug te trekken, neem contact op met de juiste (conventie) administrateurs.',
    'del_confirm'               => 'Bevestig verwijderen',
    'del_do'                    => 'Verwijder',
    'noti_greeting'             => 'Hi %NAME%,',
    'noti_bye'                  => "Tot ziens,\n   The Motion Tools Team\n\n--\n\n" .
        "Als je geen e-mails meer wilt ontvangen, kun je jezelf hier uitschrijven:\n",
    'noti_new_motion_title'     => '[Motion Tools] Nieuw voorstel:',
    'noti_new_motion_body'      => "Een nieuw voorstel is ingestuurd:\nRaadpleding: %CONSULTATION%\n" .
        "Naam: %TITLE%\nIndiener: %INITIATOR%\nLink: %LINK%",
    'noti_new_amend_title'      => '[Motion Tools] Nieuw amendement voor %TITLE%',
    'noti_new_amend_body'       => "een nieuw amendement is ingestuurd:\nRaadpleding: %CONSULTATION%\n" .
        "Motion: %TITLE%\nLink: %LINK%",
    'noti_amend_mymotion'       => "Een nieuw amendement is gepubliceerd op je voorstel:\nRaadpleding: %CONSULTATION%\n" .
        "Motie: %TITLE%\nIndiener: %INITIATOR%\nLink: %LINK%\n%MERGE_HINT%",
    'noti_amend_mymotion_merge' => "\nAls je het eens bent met dit amendement, kun je hier voorstel aanpassen (\"Adopt changes into motion\" in the sidebar)",
    'noti_new_comment_title'    => '[Motion Tools] Nieuw commentaar op %TITLE%',
    'noti_new_comment_body'     => "%TITLE% commentaar op:\n%LINK%",
    'acc_grant_email_title'     => 'Motion Tools toegang',
    'acc_grant_email_userdata'  => "Gebruikersnaam: %EMAIL%\nWachtwoord: %PASSWORD%",

    'login_title'             => 'Log in',
    'login_con_pwd_title'     => 'Log in met gebruik van het raadpledingswachtwoord',
    'login_con_pwd'           => 'Raadpleging code',
    'login_username_title'    => 'Log in met gebruikersnaam/wachtwoord',
    'login_create_account'    => 'maak een nieuw account aan',
    'login_username'          => 'E-mailadres / gebruikersnaam',
    'login_email_placeholder' => 'Je e-mailadres',
    'login_password'          => 'Wachtwoord',
    'login_password_rep'      => 'Bevestig wachtwoord',
    'login_create_name'       => 'Je naam',
    'login_captcha'           => 'Voer de code in',
    'login_btn_login'         => 'Log In',
    'login_btn_create'        => 'Maak aan',
    'login_forgot_pw'         => 'Wachtwoord vergeten?',
    'login_openid'            => 'OpenID login',
    'login_openid_url'        => 'OpenID URL',
    'login_managed_hint'      => '<strong>Hint:</strong> Nieuwe accounts moeten worden goedgekeurd.',
    'managed_account_ask_btn' => 'Vraag om toegang',
    'managed_account_asked'   => 'Toegang gevraagd.',

    'acc_request_noti_subject' => 'Vraag: Toegang tot deze site',
    'acc_request_noti_body'    => 'De gebruiker %USERNAME% (%EMAIL%) vraagt om toegang tot de site "%CONSULTATION%". Op de volgende pagina kun je die verlenen: %ACTIONLINK%',

    'login_confirm_registration' => 'Bevestig deze registratie',

    'login_err_password'      => 'Onjuist wachtwoord.',
    'login_err_username'      => 'Ongebekde gebruikersnaam.',
    'login_err_siteaccess'    => 'Deze gebruiker heeft geen toegang tot deze site.',
    'login_err_captcha'       => 'De ingevoede code komt niet overeen',
    'create_err_emailexists'  => 'Dit e-mail adres is reeds gekoppeld aan een andere gebruiker',
    'create_err_siteaccess'   => 'Het aanmaken van nieuwe gebruikers is niet toegestaan voor deze site.',
    'create_err_emailinvalid' => 'Voer een correct e-mail adres in.',
    'create_err_pwdlength'    => 'Het wachtwoord dient tenminste %MINLEN% karakters te bevatten.',
    'create_err_pwdmismatch'  => 'De wachtwoorden komen niet overeen.',
    'create_err_noname'       => 'Voer je naam in.',
    'err_contact_required'    => 'Je moet een contact adres invoeren.',

    'create_emailconfirm_title' => 'Registratie bij motion.tools',
    'create_emailconfirm_msg'   =>
        "Hi,\n\nKlik om je account te bevestigen:\n" .
        "%BEST_LINK%\n\n"
        . "...of voer de volgende code in: %CODE%\n\n"
        . "Met vriendelijke groet,\n\tTeam Motion Tools",

    'access_denied_title'  => 'Geen toegang',
    'access_denied_body'   => 'Je hebt geen toegang tot deze site.',
    'access_granted_email' => "Hi,\n\nJe hebt nu toegang tot: %LINK%\n\n"
        . "Met vriendelijke groet,\n\tTeam Motion Tools",

    'confirm_title'     => 'Bevestig te acoount',
    'confirm_username'  => 'E-mail-adres / gebruikersnaam',
    'confirm_mail_sent' => 'Er is een e-mail verstuurd. Bevestig aub ontvangst door op de link in deze mail te klikken of door de code in te voeren op deze pagina.',
    'confirm_code'      => 'Confirmatie code',
    'confirm_btn_do'    => 'Bevestig',

    'confirmed_title'         => 'Account bevestigd',
    'confirmed_msg'           => 'Je account is bevestigd, je kunt beginnen.',
    'confirmed_screening_msg' => 'Je account is aangemaakt. Adminstratie is verwittigd om je toegang tot deze site te geven.',

    'recover_title'       => 'Herstel wachtwoord',
    'recover_step1'       => '1. Vooerje e-mail-adres in',
    'recover_email_place' => 'my@email-address.org',
    'recover_send_email'  => 'Stuur een e-mail ter bevestiging',
    'recover_step2'       => '2. Stel een nieuw wachtwoord in',
    'recover_email'       => 'E-mail-adres',
    'recover_code'        => 'Bevestig code',
    'recover_new_pwd'     => 'Nieuw wachtwoord',
    'recover_set_pwd'     => 'Tel nieuw wachtwoord in',

    'recovered_title' => 'Nieuw wachtwoord ingesteld',
    'recovered_msg'   => 'Je wachtwoord is gezijzigd.',

    'deleted_title' => 'Account verwijdert',
    'deleted_msg'   => 'Je account is verwijdert.',

    'no_noti_title'        => 'Notificaties beeindigen',
    'no_noti_bc'           => 'Notificaties',
    'no_noti_unchanged'    => 'Verander de notificaties niet',
    'no_noti_consultation' => 'Beeindig notificaties van deze raadpleging (%NAME%)',
    'no_noti_all'          => 'Beeindig alle notificaties',
    'no_noti_blocklist'    => 'Geen e-mails meer <small>(ook herstel van wachtwoord emails etc.)</small>',
    'no_noti_save'         => 'Sla op',

    'notification_title' => 'E-Mail-Notificaties',
    'notification_intro' => 'Je kunt voor elke raadpleging afzonderlijk kiezen, welke notificaties je wilt ontvangen:',

    'export_title' => 'Data exporteren',
    'export_intro' => 'Je kuntb alle data over jou downloaden in machine leesbaar JSON formaat.',
    'export_btn'   => 'Download',

    'group_template_siteadmin' => 'Site admin',
    'group_template_siteadmin_h' => 'Volledige toegang voor alle raadplegingen op deze site / subdomain.',
    'group_template_consultationadmin' => 'Raadpleeg admin',
    'group_template_consultationadmin_h' => 'Volledige toegang voor deze raadpleging.',
    'group_template_proposed' => 'Voorgestelde procedure',
    'group_template_proposed_h' => 'Je kunt de voorgestelde procedure wijzigen, maar niet de moties of amendementen.',
    'group_template_participant' => 'Deelnemer',
    'group_template_participant_h' => 'Geen speciale toegang. Dat is alleen van belang als de toegang tot deze site beperkt is.',
];
