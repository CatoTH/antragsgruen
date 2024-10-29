<?php

return [
    'title_install' => 'Instalirajte Antragsgrün', // Original: Install Antragsgrün
    'err_settings_ro' => 'Postavke se ne mogu promijeniti jer se konfiguracija/config.json ne može zapisivati.
    <br>To možete popraviti Sledećom (ili sličnom) naredbom u naredbenom redu:', // Original: The settings cannot be changed as config/config.json is not writable.     <br>You can fix that by the following (or a similar) command on the command line:
    'err_php_version' => 'Antragsgrün treba PHP verziju od najmanje %MIN_VERSION%. Trenutno je instaliran %CURR_VERSION%. Obratite se administratoru sistema kako nadograditi na trenutnu verziju PHP-a.', // Original: Antragsgrün needs a PHP version of at least %MIN_VERSION%. Currently, %CURR_VERSION% is installed. Please contact your system administrator on how to upgrade to a current version of PHP.
    'language' => 'Jezik', // Original: Language
    'default_dir' => 'Zadano odredište', // Original: Default directory
    'tmp_dir' => 'Privremeno odredište', // Original: Temporary directory
    'path_lualatex' => 'Lokacija lualatexa', // Original: Location of lualatex
    'email_settings' => 'Postavke e-pošte', // Original: E-mail settings
    'email_from_address' => 'E-mail od - Adresa', // Original: E-mail from - Address
    'email_from_name' => 'E-pošta od - Ime i prezime', // Original: E-mail from - Name
    'email_transport' => 'Transport', // Original: Transport
    'email_sendmail' => 'Sendmail (lokalni)', // Original: Sendmail (local)
    'email_none' => 'Deaktiviranje e-pošte', // Original: Deactivate e-mail
    'email_smtp' => 'SMTP (vanjski server)', // Original: SMTP (external server)
    'email_mandrill' => 'Mandril', // Original: Mandrill
    'email_mailjet' => 'Mailjet', // Original: Mailjet
    'mandrill_api' => 'Mandrillov API-ključ', // Original: Mandrill\\'s API-Key
    'mailjet_api_key' => 'Mailjet\'s API-Key', // Original: Mailjet\\'s API-Key
    'mailjet_secret' => 'Mailjet\'s Secret Key', // Original: Mailjet\\'s Secret Key
    'smtp_server' => 'SMTP server', // Original: SMTP server
    'smtp_port' => 'SMTP port', // Original: SMTP port
    'smtp_login' => 'Vrsta SMTP prijave', // Original: SMTP login type
    'smtp_tls' => 'TLS', // Original: TLS
    'smtp_login_none' => 'Nema prijave', // Original: No login
    'smtp_username' => 'SMTP korisnik', // Original: SMTP user
    'smtp_password' => 'SMTP lozinka', // Original: SMTP password
    'confirm_email_addresses' => 'Potvrdite e-mail adrese novih korisnika (preporučeno!)', // Original: Confirm e-mail addresses of new users (recommended!)
    'save' => 'Sačuvaj', // Original: Save
    'saved' => 'Sačuvano.', // Original: Saved.
    'msg_site_created' => 'Baza podataka je stvorena.', // Original: The database has been created.
    'msg_config_saved' => 'Konfiguracija je spremljena.', // Original: Configuration saved.
    'created_goon_std_config' => 'Prijeđite na uobičajenu konfiguraciju', // Original: Go on to the regular configuration
    'already_created_reinit' => 'Stranica je već instalirana. <br>
            Da biste ponovno otvorili instalacijski program, stvorite Sledeću datoteku:<br>
            %FILE%', // Original: The site has already been installed.<br>             To open the Installer again, please create the following file:<br>             %FILE%
    'sidebar_curr_uses' => 'Trenutno korištena', // Original: Current used
    'sidebar_old_uses' => 'Prethodno korišteno', // Original: Previously used
    'sidebar_old_uses_show' => 'Prikaži sve', // Original: Show all
    'config_finished' => 'Osnovna instalacija je završena.', // Original: The basic installation is finished.
    'config_create_tables' => '<strong>Tablice baze podataka još nisu stvorene.</strong>
            Da biste ih stvorili, upotrijebite donju funkciju ili pozovite Sledeću naredbu naredbenog reda:
            <pre>./yii baza podataka/stvori</pre>
            SQL skripte za njihovo ručno stvaranje nalaze se ovdje:
            <pre>imovina/db/create.sql</pre>', // Original: <strong>The database tables are not created yet.</strong>             To create them, please use the function below or call the following command line command:             <pre>./yii database/create</pre>             The SQL scripts to create them manually are located here:             <pre>assets/db/create.sql</pre>
    'config_lang' => 'Jezik', // Original: Language
    'config_db' => 'Baza podataka', // Original: Database
    'config_db_type' => 'Vrsta baze podataka', // Original: Database type
    'config_db_host' => 'Naziv hosta', // Original: Hostname
    'config_db_username' => 'Korisničko ime', // Original: Username
    'config_db_password' => 'Lozinka', // Original: Password
    'config_db_password_unch' => 'Ostavite nepromijenjeno', // Original: Leave unchanged
    'config_db_no_password' => 'Nema lozinke', // Original: No password
    'config_db_dbname' => 'Naziv baze podataka', // Original: Database name
    'config_db_test' => 'Testna baza podataka', // Original: Test database
    'config_db_testing' => 'Testiranje', // Original: Testing
    'config_db_test_succ' => 'Uspjeh', // Original: Success
    'config_db_create' => 'Automatski stvorite potrebne tablice', // Original: Create necessary tables automatically
    'config_db_create_hint' => '(nije potrebno ako već postoje; ne šteti ni)', // Original: (not necessary if they are already exist; doesn\\'t harm, either)
    'config_admin' => 'Administratorski račun', // Original: Admin account
    'config_admin_already' => 'Već stvoreno.', // Original: Already created.
    'config_admin_alreadyh' => 'Ako je to greška: uklonite unose "adminUserIds" u config/config.json datoteke.', // Original: If that\\'s an error: remove the "adminUserIds"-entries in the file config/config.json.
    'config_admin_email' => 'Korisničko ime (E-mail)', // Original: Username (E-mail)
    'config_admin_pwd' => 'Lozinka', // Original: Password
    'the_site' => 'Stranica', // Original: The site
    'finish_install' => 'Zatvorite instalacijski program', // Original: Quit the Installer
    'welcome' => 'Dobrodošao!', // Original: Welcome!
    'site_err_subdomain' => 'Ovaj poddomen je već u upotrebi.', // Original: This subdomain is already in use.
    'site_err_contact' => 'Morate unijeti adresu za kontakt.', // Original: You have to enter a contact address.
    'email_mailgun' => 'Mailgun', // Original: Mailgun
    'mailgun_api' => 'Mailgunov API-ključ', // Original: Mailgun\\'s API-Key
    'mailgun_domain' => 'Domen e-pošte', // Original: E-mail domain
    'done_title' => 'Aplikacija Antragsgrün je instalirana', // Original: Antragsgrün installed
    'done_no_del_msg' => 'Izbrišite file config/INSTALACIJA da biste dovršili instalaciju.
                Ovisno o operativnom sistemu, naredba za to je nešto poput:<pre>%DELCMD%</pre>
                Nakon što to učinite, ponovno učitajte ovu stranicu.', // Original: Please delete the file config/INSTALLING to finish the installation.                 Depending on the operating system, the command for this is something like:<pre>%DELCMD%</pre>                 After doing so, reload this page.
    'done_nextstep' => 'Odlično! Sada možete postaviti još neke detalje.
                Antragsgrün je sada dostupan na Sledećoj adresi: %LINK%', // Original: Great! Now you can set up some more details.                 Antragsgrün is now available at the following address: %LINK%
    'done_goto_site' => 'Idite na stranicu', // Original: Go to the site
];