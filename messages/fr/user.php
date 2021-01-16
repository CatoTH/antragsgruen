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
        "Nom : %TITLE%\nLien : %LINK%",
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
];
