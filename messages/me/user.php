<?php

return [
    'my_acc_title' => 'Moj nalog', // Original: My account
    'my_acc_bread' => 'Podešavanja', // Original: Settings
    'email_address' => 'Adresa e-pošte', // Original: E-mail address
    'email_address_new' => 'Nova adresa e-pošte', // Original: New e-mail addresse
    'email_blocklist' => 'Blokiraj sve e-poruke na ovaj nalog', // Original: Block all e-mails to this account
    'email_unconfirmed' => 'Nepotvrđen', // Original: unconfirmed
    'pwd_confirm' => 'Potvrdite lozinku', // Original: Confirm password
    'pwd_change' => 'Promjena lozinke', // Original: Change password
    'pwd_change_hint' => 'Prazno = ostaviti nepromijenjeno', // Original: Empty = leave unchanged
    'organisation_primary' => 'Primarna organizacija', // Original: Primary organisation
    'name' => 'Ime', // Original: Name
    'name_given' => '(dato) Ime', // Original: Given name
    'name_family' => 'Prezime', // Original: Family name
    'user_group' => 'Korisnička grupa', // Original: User group
    'user_groups' => 'Korisničke grupe', // Original: User groups
    'user_groups_con' => 'Ovaj sajt', // Original: This site
    'user_groups_system' => 'Sistemske', // Original: System-wide
    '2fa_title' => 'Dvofaktorska autentifikacija', // Original: Two-Factor Authentication
    '2fa_off' => 'Nije aktino', // Original: Not active
    '2fa_activate_opener' => 'Aktiviraj', // Original: Activate
    '2fa_activated' => 'Postavi', // Original: Set up
    '2fa_remove_open' => 'Deaktivirajte dvofaktorsku autentifikaciju', // Original: Deactivate Two-Factor-Authentication
    '2fa_remove_code' => 'Unesite trenutni kod za deaktivaciju', // Original: Enter current code to deactiva
    '2fa_add_explanation' => 'Svoj nalog možete zaštititi dodavanjem drugog faktora povrh lozinke.', // Original: You can secure your account by adding a second factor on top of your password.
    '2fa_add_step1' => '1. Skenirajte QR kod pomoću aplikacije', // Original: 1. Scan the QR-Code with the app
    '2fa_add_step2' => '2. Unesite generirani kod', // Original: 2. Enter the generated code
    '2fa_img_alt' => 'QR kod za TOTP; skenirajte ovaj kod pomoću aplikacije za dvofaktorsku autentifikaciju po vašem izboru', // Original: QR-Code for TOTP; please scan this code with a Two-Factor-Authentication app of your choice
    '2fa_enter_code' => 'Unesite kod', // Original: Enter code
    '2fa_register_title' => 'Postavljanje dvofaktorske provjere autentičnosti', // Original: Set up Two-Factor Authentication
    '2fa_register_explanation' => 'Da biste ga mogli koristiti, potrebno je osigurati svoj nalog drugim faktorom.', // Original: It is necessary to secure your account with a second factor in order to use it.
    '2fa_general_explanation' => 'Koristite aplikaciju kao što je <a href="https://authy.com/download/">Authy</a>, <a href="https://freeotp.github.io">FreeOTP</a> (OpenSource), <a href="https://www.microsoft.com/de-de/security/mobile-authenticator-app">Microsoft Authenticator</a> ili Google Authenticator. <br><br>Skenirajte QR kod u nastavku, spremite ga na svoj telefon, a zatim unesite generirani numerički kod ovdje na ovoj stranici ispod.', // Original: Use an app like <a href="https://authy.com/download/">Authy</a>, <a href="https://freeotp.github.io">FreeOTP</a> (OpenSource), <a href="https://www.microsoft.com/de-de/security/mobile-authenticator-app">Microsoft Authenticator</a> or Google Authenticator.<br><br>Scan the QR-Code below, save it on your phone and then enter the generated numeric code here on this page below.
    '2fa_login_intro' => 'Osigurali ste svoj nalog drugim faktorom. Otvorite aplikaciju koju ste koristili za postavljanje i unesite kod koji prikazuje aplikacija:', // Original: You secured your account with a second factor. Please open the app you used to set it up and enter the code shown by the app:
    'force_pwd_title' => 'Promjena lozinke', // Original: Change password
    'force_pwd_explanation' => 'Postavite novu lozinku za korištenje Antragsgrüna.', // Original: Please set a new password to use Antragsgrün.
    'username_deleted' => 'Uklonjen', // Original: Removed
    'err_pwd_different' => 'Lozinke nisu iste.', // Original: The two passwords are not the same.
    'err_pwd_length' => 'Lozinka mora biti dugačka najmanje %MINLEN% znakova.', // Original: The password has to be at least %MINLEN% characters long.
    'err_pwd_fixed' => 'Lozinka ovog naloga ne može se promijeniti i stoga ne može resetirati. Za pomoć se obratite administratoru sistema.', // Original: The password of this account cannot be changed and therefore not reset. Please contact the system administrator for help.
    'err_user_acode_notfound' => 'Korisnik nije pronađen / nevažeći kod', // Original: User not found / invalid code
    'err_user_notfound' => 'Korisnički nalog %USER% nije pronađen.', // Original: The user account %USER% was not found.
    'err_code_wrong' => 'Navedeni kod je nevažeći.', // Original: The given code is invalid.
    'pwd_recovery_sent' => 'Poslana je e-pošta za reset lozinke.', // Original: A password recovery e-mail has been sent.
    'welcome' => 'Dobrodošli!', // Original: Welcome!
    'err_email_acc_notfound' => 'Ne postoji nalog s ovom e-mail adresom...?', // Original: There is not account with this e-mail address...?
    'err_email_acc_confirmed' => 'Ovaj je nalog već potvrđen.', // Original: This account is already confirmed.
    'err_invalid_email' => 'Navedena adresa e-pošte nije valjana', // Original: The given e-mail address is invalid
    'err_unknown' => 'Došlo je do nepoznate greške', // Original: An unknown error occurred
    'err_unknown_ww_repeat' => 'Došlo je do nepoznate greške.', // Original: An unknown error occurred.
    'err_no_recovery' => 'U posljednja 24 sata nije poslan nijedan zahtjev za reset.', // Original: No recovery request was sent within the last 24 hours.
    'err_change_toolong' => 'Zahtjev je prestar; Zatražite još jedan zahtjev za promjenu i potvrdite e-mail u roku od 24 sata', // Original: The request is too old; please request another change request and confirm the e-mail within 24 hours
    'err_2fa_nosession_user' => 'Nije pronađena TOTP registracija za trenutnog korisnika', // Original: No ongoing TOTP registration for the current user found
    'err_2fa_nosession' => 'U toku nije sesija prijave', // Original: No login session ongoing
    'err_2fa_timeout' => 'Molimo potvrdite drugi faktor u %minuta% minuta.', // Original: Please confirm the second factor within %minutes% minutes.
    'err_2fa_empty' => 'Dan prazan kod', // Original: Empty code given
    'err_2fa_incorrect' => 'Netačan kôd', // Original: Incorrect code provided
    'err_2fa_nocode' => 'Nije registrovan drugi faktor', // Original: No second factor registered
    'recover_mail_title' => 'Antragsgrün: Reset lozinke', // Original: Antragsgrün: Password recovery
    'recover_mail_body' => "Zdravo!\n\nTražili ste reset lozinke. " .
        "Da biste nastavili, otvorite sledeću stranicu i unesite novu lozinku:\n\n%URL%\n\n" .
        "Ili unesite sledeći kod na stranici za resetovanje: %CODE%",
    'err_recover_mail_sent' => 'U posljednja 24 sata već je poslana e-pošta za reset.', // Original: There already has been a recovery e-mail sent within the last 24 hours.
    'err_emailchange_mail_sent' => 'Već ste zatražili promjenu e-pošte u posljednja 24 sata.', // Original: You already requested an e-mail change within the last 24 hours.
    'err_emailchange_notfound' => 'Nije zatražena promjena e-pošte ili se već provodi.', // Original: No e-mail change was requested or it is already being implemented.
    'err_emailchange_flood' => 'Da bi se spriječilo preplavljivanje e-pošte, mora postojati razmak od najmanje 5 minuta između slanja dva e-maila', // Original: To prevent e-mail flooding, there needs to be a gap of at least 5 minutes between sending two e-mails
    'emailchange_mail_title' => 'Potvrdite novu adresu e-pošte', // Original: Confirm new e-mail address
    'emailchange_mail_body'     => "Zdravo!\n\nTražili ste reset lozinke. " .
        "Da biste nastavili, otvorite sledeću stranicu i unesite novu lozinku:\n\n%URL%\n\n" .
        "Ili unesite sledeći kod na stranici za resetovanje: %CODE%",
    'emailchange_sent' => 'Na ovu adresu poslan je e-mail s potvrdom. ', // Original: A confirmation e-mail has been sent to this address. 
    'emailchange_done' => 'E-mail adresa je promijenjena.', // Original: The e-mail address has been changed.
    'emailchange_requested' => 'Zatražena adresa e-pošte (još nije potvrđeno)', // Original: E-mail address requested (not confirmed yet)
    'emailchange_call' => 'promjeni', // Original: change
    'emailchange_resend' => 'Nova potvrdna e-pošta', // Original: New confirmation mail
    'email_pp_replyto' => 'Odgovori prilikom slanja predloženih postupaka (postavlja administrator)', // Original: Reply-To when sending proposed procedures (set by the admin)
    'del_title' => 'Izbriši nalog', // Original: Delete account
    'del_explanation' => 'Ovdje možete izbrisati ovaj nalog. Više nećete primati e-mailove, nakon toga neće biti moguće prijaviti.
        Vaša adresa e-pošte, ime i podaci za kontakt bit će izbrisani. <br>
        predlozi i amandmani koje ste podnijeli ostat će vidljivi. Da biste povukli već podnesene predloge, obratite se nadležnim administratorima konvencije.', // Original: Here you can delete this account. You will not receive any more e-mails, no login will be possible after this.         Your e-mail address, name and contact data will be deleted.<br>         Motions and amendments you submitted will remain visible. To withdraw already submitted motions, please contact the relevant convention administrators.
    'del_confirm' => 'Potvrdi brisanje', // Original: Confirm delete
    'del_do' => 'Izbriši', // Original: Delete
    'noti_greeting' => 'Pozdrav %NAME,', // Original: Hi %NAME%,
	'noti_bye' => "Srdačni pozdravi,\n   Antragsgrün Team\n\n--\n\n" .
        "Ako ne želite da više primate mejlove, možete se odjaviti ovdje:\n",
    'noti_new_motion_title' => '[Antragsgrün] Novi predlog:', // Original: [Antragsgrün] New motion:
    'noti_new_motion_body' => "Podnešen je novi predlog:\nKonsultacija: %CONSULTATION%\n" .
        "Name: %TITLE%\nPredlagač: %INITIATOR%\nLink: %LINK%",
    'noti_new_amend_title' => '[Antragsgrün] Nova izmjena za %TITLE%', // Original: [Antragsgrün] New amendment for %TITLE%
	'noti_new_amend_body' => "Podnešen je novi amandman:\nKonsultacija: %CONSULTATION%\n" .
        "Name: %TITLE%\nPredlagač: %INITIATOR%\nLink: %LINK%",
    'noti_amend_mymotion' => "Novi amandman je objavljen na vaš predlog:\nKonsultacija: %CONSULTATION%\n" .
        "Predlog: %TITLE%\nPredlagač: %INITIATOR%\nLink: %LINK%\n%MERGE_HINT%",
    'noti_amend_mymotion_merge' => "\nAko se slažete sa ovim amandmanom, možete usvojiti izmjene (\"Usvoji izmjene u predlogu\" u bočnoj traci)",
    'noti_new_comment_title' => '[Antragsgrün] Novi komentar za %TITLE%', // Original: [Antragsgrün] New comment to %TITLE%
    'noti_new_comment_body'     => "%TITLE% je komentarisan:\n%LINK%",
    'acc_grant_email_title' => 'Aplikacija Antragsgrün', // Original: Antragsgrün access
	'acc_grant_email_userdata'  => "E-mail / korisničko ime: %EMAIL%\nŠifra: %PASSWORD%",
    'login_title' => 'Prijava', // Original: Login
    'login_con_pwd_title' => 'Prijavite se pomoću lozinke za konsultacije', // Original: Login using the consultation-password
    'login_con_pwd' => 'Lozinka za konsultacije', // Original: Consultation-password
    'login_username_title' => 'Prijavite se pomoću korisničkog imena/lozinke', // Original: Login using username/password
    'login_create_account' => 'Napravite novi nalog', // Original: Create a new account
    'login_username' => 'Adresa e-pošte / korisničko ime', // Original: E-mail address / username
    'login_email_placeholder' => 'Vaša adresa e-pošte', // Original: Your e-mail address
    'login_password' => 'Lozinka', // Original: Password
    'login_password_rep' => 'Lozinka (potvrdi)', // Original: Password (Confirm)
    'login_create_name' => 'Tvoje ime', // Original: Your name
    'login_captcha' => 'Unesite prikazani kod', // Original: Please enter the code shown
    'login_btn_login' => 'Prijavi se', // Original: Log In
    'login_btn_create' => 'Kreiraj', // Original: Create
    'login_forgot_pw' => 'Zaboravili ste lozinku?', // Original: Forgot your password?
    'login_openid' => 'OpenID prijava', // Original: OpenID login
    'login_openid_url' => 'OpenID URL', // Original: OpenID URL
    'login_managed_hint' => '<strong>Savjet:</strong> nove naloge mora pregledati administrator prije nego što mogu pristupiti ovoj stranici.', // Original: <strong>Hint:</strong> new accounts have to be reviewed by an admin before they can access this site.
    'managed_account_ask_btn' => 'Zatražite dopuštenje', // Original: Ask for permission
    'managed_account_asked' => 'Zatražio dopuštenje.', // Original: Asked for permission.
    'acc_request_noti_subject' => 'Zahtjev: Pristup stranici', // Original: Request: Access to the site
    'acc_request_noti_body' => 'Korisnik %USERNAME% (%EMAIL%) traži pristup stranici "%CONSULTATION%". Na Sledećoj stranici možete dodijeliti dopuštenje: %ACTIONLINK%', // Original: The user %USERNAME% (%EMAIL%) asks for access to the site "%CONSULTATION%". On the following page you can grant the permission: %ACTIONLINK%
    'login_confirm_registration' => 'Potvrdite registraciju', // Original: Confirm the registration
    'login_err_password' => 'Nevažeća lozinka.', // Original: Invalid password.
    'login_err_username' => 'Korisničko ime nije pronađeno.', // Original: Username not found.
    'login_err_siteaccess' => 'Ovaj nalog ne ispunjava uslove za prijavu na ovu stranicu.', // Original: This account is not eligible to log in to this site.
    'login_err_captcha' => 'Uneseni kod nije odgovarao slici', // Original: The entered code did not match the image
    'create_err_emailexists' => 'Ova e-mail adresa je već registrovana na drugom nalogu', // Original: This e-mail-address is already registered to another account
    'create_err_siteaccess' => 'Stvaranje naloga nije dopušteno za ovu stranicu.', // Original: Creating accounts is not allowed for this site.
    'create_err_emailinvalid' => 'Unesite valjanu adresu e-pošte.', // Original: Please enter a valid e-mailaddress.
    'create_err_pwdlength' => 'Lozinka mora imati najmanje %MINLEN% znakova.', // Original: The password needs to be at least %MINLEN% characters long.
    'create_err_pwdmismatch' => 'Dvije unesene lozinke se ne podudaraju.', // Original: The two passwords entered do not match.
    'create_err_noname' => 'Unesite svoje ime.', // Original: Please enter your name.
    'err_contact_required' => 'Morate unijeti adresu kontakta.', // Original: You need to enter a contact address.
    'create_emailconfirm_title' => 'Registracija na Antragsgrün / motion.tools', // Original: Registration at Antragsgrün / motion.tools
    'create_emailconfirm_msg'   =>
        "Zdravo,\n\nkliknite na sledeći link da potvrdite svoj nalog:\n" .
        "%BEST_LINK%\n\n"
        . "... ili unesite sledeći kod na sajtu: %CODE%\n\n"
        . "S poštovanjem,\n\tTeam Antragsgrün",
    'access_denied_title' => 'Nema pristupa', // Original: No access
    'access_denied_body' => 'Nemate pristup ovoj stranici.', // Original: You don\\' have access to this site.
    'access_granted_email' => "Zdravo,\n\nUpravo ste dobili pristup: %LINK%\n\n"
        . "S poštovanjem,\n\tTeam Antragsgrün",
    'confirm_title' => 'Potvrdite svoj nalog', // Original: Confirm your account
    'confirm_username' => 'E-mail adresa / korisničko ime', // Original: E-mail-address / username
    'confirm_mail_sent' => 'E-pošta je upravo poslana na vašu adresu. Molimo potvrdite primanje ove pošte klikom na poveznicu u pošti ili unosom navedenog koda na ovoj stranici.', // Original: An email was just sent to your address. Please confirm receiving this mail by clicking on the link in the mail or by entering the given code on this page.
    'confirm_code' => 'Potvrdni kod', // Original: Confirmation code
    'confirm_btn_do' => 'Potvrdi', // Original: Confirm
    'confirm_resend' => 'Ponovno pošaljite e-poštu s potvrdom', // Original: Resend confirmation e-mail
    'confirmed_title' => 'nalog potvrđen', // Original: Account confirmed
    'confirmed_msg' => 'Sve je spremno! Vaš nalog je potvrđen.', // Original: Your\\'re all set! Your account is confirmed and you are good to go.
    'confirmed_screening_msg' => 'Vaš je nalog sada ispravan. Administrator je obaviješten da vam odobri pristup ovoj stranici.', // Original: Your account is now valid. The admin has been notified to grant you access to this site.
    'recover_title' => 'Reset lozinke', // Original: Password recovery
    'recover_step1' => '1. Unesite svoju adresu e-pošte', // Original: 1. Enter your e-mail-address
    'recover_email_place' => 'my@email-address.org', // Original: my@email-address.org
    'recover_send_email' => 'Pošalji potvrdu-e-mail', // Original: Send confirmation-e-mail
    'recover_step2' => '2. Postavite novu lozinku', // Original: 2. Set a new password
    'recover_email' => 'Adresa e-pošte', // Original: E-mail-address
    'recover_code' => 'Potvrdni kod', // Original: Confirmation code
    'recover_new_pwd' => 'Nova lozinka', // Original: New password
    'recover_set_pwd' => 'Postavljanje nove lozinke', // Original: Set new password
    'recovered_title' => 'Novi set lozinki', // Original: New password set
    'recovered_msg' => 'Vaša lozinka je promijenjena.', // Original: Your password has been changed.
    'deleted_title' => 'Nalog izbrisan', // Original: Account deleted
    'deleted_msg' => 'Vaš je nalog izbrisan.', // Original: Your account has been deleted.
    'no_noti_title' => 'Otkažite pretplatu na obavještena', // Original: Unsubscribe from notifications
    'no_noti_bc' => 'Obavještenja', // Original: Notifications
    'no_noti_unchanged' => 'Ostavite obavještenja kakve jesu', // Original: Leave the notifications as they are
    'no_noti_consultation' => 'Otkažite pretplatu na obavještenja o ovoj konsultaciji (%NAME%)', // Original: Unsubscribe from notifications of this consultation (%NAME%)
    'no_noti_all' => 'Otkažite pretplatu na sva obavještenja', // Original: Unsubscribe from all notifications
    'no_noti_blocklist' => 'Uopšte nema e-pošte <small>(uključujući e-poštu za Reset lozinke itd.)</small>', // Original: No e-mails at all <small>(including password-recovery-emails etc.)</small>
    'no_noti_save' => 'Sačuvaj', // Original: Save
    'notification_title' => 'Obavještenja e-poštom', // Original: E-Mail-Notifications
    'notification_intro' => 'Za svaku konsultaciju pojedinačno možete odabrati o čemu želite biti obaviješteni:', // Original: You can choose for each consultation individually, what you want to be notified about:
    'export_title' => 'Eksport podataka', // Original: Data export
    'export_intro' => 'Sve lične podatke spremljene o vama možete preuzeti na discuss.green u mašinski čitljivom JSON formatu.', // Original: You can download all personal data saved about you on discuss.green in machine readable JSON format.
    'export_btn' => 'Preuzmi', // Original: Download
    'group_template_siteadmin' => 'Administrator stranice', // Original: Site admin
    'group_template_siteadmin_h' => 'Sve privilegije za sve konsultacije na ovoj stranici / poddomeni.', // Original: All privileges to all consultations on this site / subdomain.
    'group_template_consultationadmin' => 'Administrator za konsultacije', // Original: Consultation admin
    'group_template_consultationadmin_h' => 'Sve privilegije za ovu konsultaciju.', // Original: All privileges to this one consultation.
    'group_template_proposed' => 'Predloženi postupak', // Original: Proposed procedure
    'group_template_proposed_h' => 'Može uređivati predloženi postupak, ali ne i same predloge i amandmane.', // Original: Can edit the proposed procedure, but not the motions and amendments themselves.
    'group_template_progress' => 'Izvještaji o napretku', // Original: Progress reports
    'group_template_progress_h' => 'Može uređivati izvještaje o napretku rješenja, ali ne i sama rješenja.', // Original: Can edit progress reports of resolutions, but not the resolutions themselves.
    'group_template_participant' => 'Učesnik', // Original: Participant
    'group_template_participant_h' => 'Nema posebnih privilegija. Relevantno samo ako je pristup ovoj stranici ograničen.', // Original: No special privileges. Only relevant if access to this site is restricted.
];