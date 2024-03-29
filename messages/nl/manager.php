<?php

return [
    'title_install'           => 'Installeer Motion Tools',
    'err_settings_ro'         => 'The settings cannot be changed as config/config.json is not writable.
    <br>You can fix that by the following (or a similar) command on the command line:',
    'err_php_version'         => 'Motion Tools needs a PHP version of at least 5.6, with 7.0 or greater being recommended. You can try to install with your current version (%VERSION%), however problems will probably occur rather sooner than later. Please contact your system administrator on how to upgrade to a current version of PHP.',
    'language'                => 'Taal',
    'default_dir'             => 'Default directory',
    'tmp_dir'                 => 'Temporary directory',
    'path_lualatex'           => 'Location of lualatex',
    'email_settings'          => 'E-mail settings',
    'email_from_address'      => 'E-mail from - Address',
    'email_from_name'         => 'E-mail from - Name',
    'email_transport'         => 'Transport',
    'email_sendmail'          => 'Sendmail (local)',
    'email_none'              => 'Deactivate e-mail',
    'email_smtp'              => 'SMTP (external server)',
    'email_mandrill'          => 'Mandrill',
    'email_mailjet'           => 'Mailjet',
    'mandrill_api'            => 'Mandrill\'s API-Key',
    'mailjet_api_key'         => 'Mailjet\'s API-Key',
    'mailjet_secret'          => 'Mailjet\'s Secret Key',
    'smtp_server'             => 'SMTP server',
    'smtp_port'               => 'SMTP port',
    'smtp_login'              => 'SMTP login type',
    'smtp_tls'                => 'TLS',
    'smtp_login_none'         => 'No login',
    'smtp_username'           => 'SMTP user',
    'smtp_password'           => 'SMTP password',
    'confirm_email_addresses' => 'Confirm e-mail addresses of new users (recommended!)',
    'save'                    => 'Opslaan',
    'saved'                   => 'Opgeslagen.',
    'msg_site_created'        => 'The database has been created.',
    'msg_config_saved'        => 'Configuratie opgeslagen.',
    'created_goon_std_config' => 'Go on to the regular configuration',
    'already_created_reinit'  => 'The site has already been installed.<br>
            To open the Installer again, please create the following file:<br>
            %FILE%',
    'sidebar_curr_uses'       => 'Current used',
    'sidebar_old_uses'        => 'Previously used',
    'sidebar_old_uses_show'   => 'Show all',
    'config_finished'         => 'The basic installation is finished.',
    'config_create_tables'    => '<strong>The database tables are not created yet.</strong>
            To create them, please use the function below or call the following command line command:
            <pre>./yii database/create</pre>
            The SQL scripts to create them manually are located here:
            <pre>assets/db/create.sql</pre>',
    'config_lang'             => 'Taal',
    'config_db'               => 'Database',
    'config_db_type'          => 'Database type',
    'config_db_host'          => 'Hostname',
    'config_db_username'      => 'Username',
    'config_db_password'      => 'Password',
    'config_db_password_unch' => 'Leave unchanged',
    'config_db_no_password'   => 'No password',
    'config_db_dbname'        => 'Database name',
    'config_db_test'          => 'Test database',
    'config_db_testing'       => 'Testing',
    'config_db_test_succ'     => 'Success',
    'config_db_create'        => 'Create necessary tables automatically',
    'config_db_create_hint'   => '(not necessary if they are already exist; doesn\'t harm, either)',
    'config_admin'            => 'Admin account',
    'config_admin_already'    => 'Already created.',
    'config_admin_alreadyh'   => 'If that\'s an error: remove the "adminUserIds"-entries in the file config/config.json.',
    'config_admin_email'      => 'Username (E-mail)',
    'config_admin_pwd'        => 'Password',
    'the_site'                => 'The site',
    'finish_install'          => 'Quit the Installer',
    'welcome'                 => 'Welkom!',
    'site_err_subdomain'      => 'Dit subdomein is al in gebruik.',
    'site_err_contact'        => 'Je moet een contactadres opgeven.',
    'email_mailgun'           => 'Mailgun',
    'mailgun_api'             => 'Mailgun\'s API-Key',
    'mailgun_domain'          => 'E-mail domain',

    'done_title' => 'Motion Tools is geïnstalleerd',
    'done_no_del_msg' => 'Please delete the file config/INSTALLING to finish the installation.
                Depending on the operating system, the command for this is something like:<pre>%DELCMD%</pre>
                After doing so, reload this page.',
    'done_nextstep' => 'Great! Now you can set up some more details.
                Motion Tools is now available at the following address: %LINK%',
    'done_goto_site' => 'Go to the site',
];