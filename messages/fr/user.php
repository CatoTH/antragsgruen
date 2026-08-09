<?php
return [
    'my_acc_title'              => 'Mon compte',
    'my_acc_bread'              => 'Paramètres',
    'email_address'             => 'Addresse email',
    'email_address_new'         => 'Nouvelle adresse email',
    'email_blocklist'           => 'Bloquer tous les emails de ce compte',
    'email_unconfirmed'         => 'non-confirmé',
    'pwd_confirm'               => 'Confirmer le mot-de-passe',
    'pwd_change'                => 'Changer le mot de passe',
    'pwd_change_hint'           => 'Vide = laiser inchangé',
    'name'                      => 'Nom',
    'err_pwd_different'         => 'Les deux mots de passe ne sont pas identiques.',
    'err_pwd_length'            => 'Le mot de passe doit être long d\'au moins %MINLEN% caractères.',
    'err_user_acode_notfound'   => 'Utilisateur inconnu / mot de passe invalide',
    'err_user_notfound'         => 'Le compte utilisateur %USER% n\'a pas été trouvé.',
    'err_code_wrong'            => 'Le code entré est invalide.',
    'pwd_recovery_sent'         => 'Un email vous a été envoyé afin de récupérer votre mot de passe.',
    'welcome'                   => 'Bienvenue!',
    'err_email_acc_notfound'    => 'Il n\'y a pad de compte associé à cette adresse email...',
    'err_invalid_email'         => 'L\'adresse email donnée est invalide',
    'err_unknown'               => 'Une erreur inconnue s\'est produite',
    'err_unknown_ww_repeat'     => 'Une erreur inconnue s\'est produite',
    'err_no_recovery'           => 'Aucune demande de récupération du mot de passe n\'a été envoyée dans les dernièrs 24 heures.',
    'err_change_toolong'        => 'La demande est trop ancienne; merci de faire une nouvelle demande de récupération de mot de passe et de confirmer l\'email dans les 24 heures',
    'recover_mail_title'        => 'Antragsgrün: Récupération de mot de passe',
    'recover_mail_body'         => "Salut!\n\nTu as fait une demande de récupération de mot de passe. " .
        "Pour obtenir un mot de passe, cliquer sur le lien suivant et entre ton nouveau mot de passe :\n\n%URL%\n\n" .
        "Ou entre le code suivant sur le page de récupération du mot de passe : %CODE%",
    'err_recover_mail_sent'     => 'Un email de récupération du mot de passe a déjà été envoyé dans les dernières 24 heures.',
    'err_emailchange_mail_sent' => 'Tu as déjà demandé un changement d\'email dans les dernières 24 heures.',
    'err_emailchange_notfound'  => 'Aucun changement d\'email n\' a été demandé ou alors il a déjà été mis en place.',
    'err_emailchange_flood'     => 'Pour éviter les spams, il doit y aboir un vide d\'au moins 5 minutes entre les envois d\'emails',
    'emailchange_mail_title'    => 'Confirmer la nouvelle adresse email',
    'emailchange_mail_body'     => "Salut!\n\nTu as demandé à changer d\'adresse email. " .
        "Pour confirmer, merci de cliquer sur le lien :\n\n%URL%\n\n",
    'emailchange_sent'          => 'Un email de confirmation a été envoyé à cette adresse. ' .
        'Clique sur le lien présent dans l\'email pour changer ton adresse.',
    'emailchange_done'          => 'L\' adresse email a été modifiée.',
    'emailchange_requested'     => 'Adresse email requise (pas encore confirmée)',
    'emailchange_call'          => 'modifier',
    'emailchange_resend'        => 'Nouvel email de confirmation',
    'del_title'                 => 'Supprimer le compte',
    'del_explanation'           => 'Tu peux supprimer ce compte ici. Tu ne recevras plus d\emails, il ne sera pas possible de se connecter après cela.
        Ton adresse email, ton nom et des données de contact seront supprimées.<br>
        Les motions et les amendements que tu as déposé resteront visibles. Pour retirer des motions déjà déposées, contacte les admistrateurs de la convention.',
    'del_confirm'               => 'Confirmer la suppression',
    'del_do'                    => 'Supprimer',
    'noti_greeting'             => 'Hi %NAME%,',
    'noti_bye'                  => "Bien à tou,\n   L'équipe Antragsgrün\n\n--\n\n" .
        "Si tu ne souhaites plus recevoir d'emails, tu peux te désinscrire ici :\n",
    'noti_new_motion_title'     => '[Antragsgrün] Nouvelle motion:',
    'noti_new_motion_body'      => "Une nouvelle motion a été déposée :\nConsultation : %CONSULTATION%\n" .
        "Nom : %TITLE%\nAuteur : %INITIATOR%\nLien : %LINK%",
    'noti_new_amend_title'      => '[Antragsgrün] Nouvel amendement à %TITLE%',
    'noti_new_amend_body'       => "Un nouvel amendement a été déposé :\nConsultation : %CONSULTATION%\n" .
        "Motion : %TITLE%\nLien : %LINK%",
    'noti_new_comment_title'    => '[Antragsgrün] Nouveau commentaire à %TITLE%',
    'noti_new_comment_body'     => "%TITLE% a été commenté :\n%LINK%",
    'acc_grant_email_title'     => 'Accès à Antragsgrün',
    'acc_grant_email_userdata' => "Email / nom d'utilisateur : %EMAIL%\nMot de passe : %PASSWORD%",


    'login_title'             => 'Connexion',
    'login_username_title'    => 'Se connecter avec son nom d\'utilisateur / mot de passe',
    'login_create_account'    => 'Créer un nouveau compte',
    'login_username'          => 'Adresse email / nom d\'utilisateur',
    'login_email_placeholder' => 'Ton adresse email',
    'login_password'          => 'Mot de passe',
    'login_password_rep'      => 'Mot de passe (Confirmer)',
    'login_create_name'       => 'Ton nom',
    'login_btn_login'         => 'Connexion',
    'login_btn_create'        => 'Créer',
    'login_forgot_pw'         => 'Tu as oublié ton mot de passe ?',
    'login_openid'            => 'OpenID login',
    'login_openid_url'        => 'OpenID URL',

    'login_err_password'      => 'Mot de passe invalide.',
    'login_err_username'      => 'Nom d\'utilisateur introuvable.',
    'login_err_siteaccess'    => 'Ce compte ne peut pas se connecter sur ce site.',
    'create_err_emailexists'  => 'Cette adresse email est déjà liée à un autre compte',
    'create_err_siteaccess'   => 'Il n\'esst pas possible de créer des compte pour ce site.',
    'create_err_emailinvalid' => 'Merci d\'entrer une adresse email valide.',
    'create_err_pwdlength'    => 'Le mot de passe doit être long d\'au moins  %MINLEN% caractères.',
    'create_err_pwdmismatch'  => 'Les deux mots de passes entrés ne sont pas les mêmes.',
    'create_err_noname'       => 'Merci d\'entrer votre nom.',
    'err_contact_required'    => 'Tu dois entrer une adresse de contact.',

    'create_emailconfirm_title' => 'Inscription sur Antragsgrün / motion.tools',
    'create_emailconfirm_msg'   =>
        "Salut,\n\nmerci de cliquer sur le lien suivant pour confirmer votre compte :\n" .
        "%BEST_LINK%\n\n"
        . "...ou entrer le code suivant sur le site : %CODE%\n\n"
        . "Bien à toi,\n\tL'équipe Antragsgrün",

    'access_denied_title' => 'Accès refusé',
    'access_denied_body'  => 'Tu n\'as pas accès au site.',

    'confirm_title'     => 'Confirmer ton compte',
    'confirm_username'  => 'Addresse email / nom d\'utilisateur',
    'confirm_mail_sent' => 'Un email a tout juste été envoyé à ton adresse. Merci de confirmer avoir reçu ce mail en cliquant sur le lien du mail ou en entrant le code reçu sur cette page.',
    'confirm_code'      => 'Code de confirmation',
    'confirm_btn_do'    => 'Confirmer',

    'confirmed_title' => 'Compte confirmé',
    'confirmed_msg'   => 'Tout est bon ! Ton compte est confirmé, tu peux y aller.',

    'recover_title'       => 'Récupération du mot de passe',
    'recover_step1'       => '1. Entre ton adresse email',
    'recover_email_place' => 'mon@adresse-email.org',
    'recover_send_email'  => 'Envoyer l\'email de confirmation',
    'recover_step2'       => '2. Définis un nouveau mot de passe',
    'recover_email'       => 'Adresse email',
    'recover_code'        => 'Code de confirmation',
    'recover_new_pwd'     => 'Nouveau mot de passe',
    'recover_set_pwd'     => 'Définir le nouveau mot de passe',

    'recovered_title' => 'Nouveau mot de passe défini',
    'recovered_msg'   => 'Ton mot de passe a été modifié.',

    'deleted_title' => 'Compte supprimé',
    'deleted_msg'   => 'Ton compte a été supprimé.',

    'no_noti_title'        => 'Se désinscrire des notifications',
    'no_noti_bc'           => 'Notifications',
    'no_noti_unchanged'    => 'Laisser les notifications comme elles sont',
    'no_noti_consultation' => 'Se désinscire des notifications de cette consultation (%NAME%)',
    'no_noti_all'          => 'Se désinscrire de toutes les notifications',
    'no_noti_blocklist'    => 'Aucun email du tout<small>(y compris les emails de récupération de mot de passe, etc.)</small>',
    'no_noti_save'         => 'Enregistrer',

    'pw_x_chars' => [
        'text' => 'The password needs to be at least %NUM% characters long.',
        'js' => true,
    ],
    'pw_min_x_chars' => [
        'text' => 'Min. %NUM% characters',
        'js' => true,
    ],
    'pw_no_match' => [
        'text' => 'The passwords do not match.',
        'js' => true,
    ],

    'organisation_primary' => 'Organisation principale',
    'name_given'            => 'Prénom',
    'name_family'           => 'Nom de famille',
    'organization'         => 'Organisation',
    'user_group'            => 'Groupe d\'utilisateurs',
    'user_groups'           => 'Groupes d\'utilisateurs',
    'user_groups_con'       => 'Ce site',
    'user_groups_system'    => 'Général',

    '2fa_title'                 => 'Authentification à deux facteurs',
    '2fa_off'                   => 'Inactif',
    '2fa_activate_opener'       => 'Activer',
    '2fa_activated'             => 'Configuré',
    '2fa_remove_open'           => 'Désactiver l\'authentification à deux facteurs',
    '2fa_remove_code'           => 'Entre le code actuel pour désactiver',
    '2fa_add_explanation'       => 'Tu peux sécuriser ton compte en ajoutant un second facteur en plus de ton mot de passe.',
    '2fa_add_step1'             => '1. Scanne le QR-code avec l\'application',
    '2fa_add_step2'             => '2. Entre le code généré',
    '2fa_img_alt'               => 'QR-code pour TOTP ; merci de scanner ce code avec une application d\'authentification à deux facteurs de ton choix',
    '2fa_enter_code'            => 'Entre le code',
    '2fa_register_title'        => 'Configurer l\'authentification à deux facteurs',
    '2fa_register_explanation'  => 'Il est nécessaire de sécuriser ton compte avec un second facteur pour pouvoir l\'utiliser.',
    '2fa_general_explanation'   => 'Utilise une application comme <a href="https://authy.com/download/">Authy</a>, <a href="https://freeotp.github.io">FreeOTP</a> (OpenSource), <a href="https://www.microsoft.com/de-de/security/mobile-authenticator-app">Microsoft Authenticator</a> ou Google Authenticator.<br><br>Scanne le QR-code ci-dessous, enregistre-le sur ton téléphone, puis entre le code numérique généré ici, plus bas sur cette page.',
    '2fa_login_intro'           => 'Tu as sécurisé ton compte avec un second facteur. Merci d\'ouvrir l\'application que tu as utilisée pour le configurer et d\'entrer le code qu\'elle affiche :',

    'force_pwd_title'       => 'Changer le mot de passe',
    'force_pwd_explanation' => 'Merci de définir un nouveau mot de passe pour utiliser Antragsgrün.',

    'username_deleted' => 'Supprimé',

    'err_pwd_fixed'          => 'Le mot de passe de ce compte ne peut pas être modifié et ne peut donc pas être réinitialisé. Merci de contacter l\'administrateur système pour obtenir de l\'aide.',
    'err_email_acc_confirmed' => 'Ce compte est déjà confirmé.',
    'err_2fa_nosession_user' => 'Aucune inscription TOTP en cours trouvée pour cet utilisateur',
    'err_2fa_nosession'      => 'Aucune session de connexion en cours',
    'err_2fa_timeout'        => 'Merci de confirmer le second facteur dans un délai de %minutes% minutes.',
    'err_2fa_empty'          => 'Code vide',
    'err_2fa_incorrect'      => 'Code incorrect',
    'err_2fa_nocode'         => 'Aucun second facteur enregistré',

    'email_pp_replyto' => 'Reply-To lors de l\'envoi des procédures proposées (défini par l\'admin)',

    'noti_amend_mymotion' => "Un nouvel amendement a été publié pour ta motion :\nConsultation : %CONSULTATION%\nMotion : %TITLE%\nAuteur : %INITIATOR%\nLien : %LINK%\n%MERGE_HINT%",
    'noti_amend_mymotion_merge' => "\nSi tu es d'accord avec cet amendement, tu peux intégrer les modifications (« Intégrer dans la motion » dans la sidebar)",

    'login_con_pwd_title' => 'Connexion avec le mot de passe de la consultation',
    'login_con_pwd'       => 'Mot de passe de la consultation',
    'login_captcha'       => 'Merci d\'entrer le code affiché',
    'login_managed_hint'  => '<strong>Remarque :</strong> les nouveaux comptes doivent être validés par un admin avant de pouvoir accéder à ce site.',

    'managed_account_ask_btn' => 'Demander l\'autorisation',
    'managed_account_asked'   => 'Autorisation demandée.',

    'acc_request_noti_subject' => 'Demande : accès au site',
    'acc_request_noti_body'    => 'L\'utilisateur %USERNAME% (%EMAIL%) demande l\'accès au site « %CONSULTATION% ». Sur la page suivante, tu peux valider cette demande : %ACTIONLINK%',

    'login_confirm_registration' => 'Confirmer l\'inscription',
    'login_err_captcha'          => 'Le code entré ne correspond pas à l\'image',
    'login_err_nocaptcha'        => 'Merci d\'entrer le code affiché sur l\'image.',

    'access_granted_email' => "Salut,\n\nTu viens d'obtenir l'accès à : %LINK%\n\nBien à toi,\n\tL'équipe Antragsgrün",

    'confirm_resend'          => 'Renvoyer l\'email de confirmation',
    'confirmed_screening_msg' => 'Ton compte est maintenant validé. L\'admin a été prévenu pour t\'accorder l\'accès à ce site.',

    'notification_title' => 'Notifications email',
    'notification_intro' => 'Tu peux choisir, pour chaque consultation individuellement, de quoi tu souhaites être notifié :',

    'export_title' => 'Export des données',
    'export_intro' => 'Tu peux télécharger ici toutes les données personnelles enregistrées à ton sujet dans Antragsgrün, dans un format JSON lisible par une machine.',
    'export_btn'   => 'Télécharger',

    'group_template_siteadmin'            => 'Admin du site',
    'group_template_siteadmin_h'          => 'Tous les privilèges sur toutes les consultations de ce site / sous-domaine.',
    'group_template_consultationadmin'    => 'Admin de la consultation',
    'group_template_consultationadmin_h'  => 'Tous les privilèges sur cette consultation.',
    'group_template_proposed'             => 'Procédure proposée',
    'group_template_proposed_h'           => 'Peut modifier la procédure proposée, mais pas les motions et amendements eux-mêmes.',
    'group_template_progress'             => 'Rapports d\'avancement',
    'group_template_progress_h'           => 'Peut modifier les rapports d\'avancement des résolutions, mais pas les résolutions elles-mêmes.',
    'group_template_participant'          => 'Participant',
    'group_template_participant_h'        => 'Aucun privilège particulier. Seulement pertinent si l\'accès à ce site est restreint.',
];
