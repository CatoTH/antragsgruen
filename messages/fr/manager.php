<?php

return [
    'title_install'           => 'Installer Antragsgrün',
    'err_settings_ro'         => 'Les paramètres ne peuvent pas être modifiés car il n\'est pas possible d\'écrire sur config/config.json.
    <br>Tu peux réparer cela en entran la commande suivante (ou une similaire) dans l\'invite de commande :',
    'language'                => 'Langue',
    'default_dir'             => 'Répertoire par défault',
    'tmp_dir'                 => 'Répertoire temporaire',
    'path_lualatex'           => 'Localisation de lualatex',
    'email_settings'          => 'Paramètres des emails',
    'email_from_address'      => 'Adresse d\'expéditeur des emails',
    'email_from_name'         => 'Nom d\'expéditeur des emails',
    'email_transport'         => 'Transport',
    'email_sendmail'          => 'Sendmail (local)',
    'email_none'              => 'Désactiver les emails',
    'email_smtp'              => 'SMTP (external server)',
    'email_mandrill'          => 'Mandrill',
    'mandrill_api'            => 'Mandrill\'s API-Key',
    'smtp_server'             => 'Serveur SMTP',
    'smtp_port'               => 'Port SMTP',
    'smtp_login'              => 'Type de login SMTP',
    'smtp_login_none'         => 'Pas de login',
    'smtp_username'           => 'Utilisateur SMTP',
    'smtp_password'           => 'Mot de passe SMTP',
    'confirm_email_addresses' => 'Confirmer les adresses emails des nouveaux utilisateurs (recommandé !)',
    'save'                    => 'Sauvergarder',
    'saved'                   => 'Sauvegardé.',
    'msg_site_created'        => 'La base de données a été créée.',
    'msg_config_saved'        => 'Configuration sauvegardée.',
    'created_goon_std_config' => 'Continuer vers la configuration classique',
    'already_created_reinit'  => 'Le site a déjà été installé.<br>
            Pour ouvrir à nouveau l\'installer, merci de créer le fichier suivante:<br>
            %FILE%',
    'sidebar_curr_uses'       => 'Actuellement utilisé',
    'sidebar_old_uses'        => 'Précédemment utilisé',
    'sidebar_old_uses_show'   => 'Montrer tout',
    'config_finished'         => 'L\'installation de base est terminée.',
    'config_create_tables'    => '<strong>Les tableaux de la base de données n\'ont pas encore été créés.</strong>
            Pour les créer, merci d\'utiliser la fonction ci-dessous ou d\'entrer la ligne de commande suivante :
            <pre>./yii database/create</pre>
            Les scripts SQL pour les créer manuellement sont situés ici :
            <pre>assets/db/create.sql</pre>',
    'config_lang'             => 'Langue',
    'config_db'               => 'Base de données',
    'config_db_type'          => 'Type de base de données',
    'config_db_host'          => 'Hostname',
    'config_db_username'      => 'Username',
    'config_db_password'      => 'Mot de passe',
    'config_db_password_unch' => 'Laissé inchangé',
    'config_db_no_password'   => 'Pas de mot de passe',
    'config_db_dbname'        => 'Nom de la base de données',
    'config_db_test'          => 'Tester la base de données',
    'config_db_testing'       => 'Test...',
    'config_db_test_succ'     => 'Succès',
    'config_db_create'        => 'Créer les tableaux nécessaires automatiquement',
    'config_db_create_hint'   => '(pas nécessaire s\ils existent déjà ; mais ça ne fait pas de mal dans tous les cas)',
    'config_admin'            => 'Compte administrateur',
    'config_admin_already'    => 'Déjà créé.',
    'config_admin_alreadyh'   => 'S\'il y a une erreur : supprimer les entrées "adminUserIds" dans le fichier config/config.json.',
    'config_admin_email'      => 'Nom d\'utilisateur (Email)',
    'config_admin_pwd'        => 'Mot de passe',
    'the_site'                => 'Le site',
    'finish_install'          => 'Quitter l\'installeur',
    'welcome'                 => 'Bienvenue!',
    'site_err_subdomain'      => 'Ce sous-domaine est déjà utilisé.',
    'site_err_contact'        => 'Vous devez entrer une adresse de contact.',
    'email_mailgun'           => 'Mailgun',
    'mailgun_api'             => 'Mailgun\'s API-Key',
    'mailgun_domain'          => 'Domaine email',

    'done_title' => 'Antragsgrün installé',
    'done_no_del_msg' => 'Merci de supprimer le fichier config/INSTALLING pour finir l\'installation.
                En fonction de votre OS, la commande pour le faire est quelque chose comme : <pre>%DELCMD%</pre>
                Après l\'avoir fait, recharge cette page.',
    'done_nextstep' => 'Super ! Tu peux maintenant régler plus de détails.
                Antragsgrün est maintenant disponible à l\'adresse suivante : %LINK%',
];
