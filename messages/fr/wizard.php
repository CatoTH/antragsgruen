<?php

return [
    'title' => 'Créer un site',

    'step_language'   => 'Langue',
    'step_purpose'    => 'Objecitf',
    'step_motions'    => 'Motions',
    'step_amendments' => 'Amendements',
    'step_special'    => 'Cas spéciaux',
    'step_site'       => 'Questions organisationnelles',

    'next'   => 'Suivant',
    'prev'   => 'Retour',
    'finish' => 'Prêt / Créer le site',

    'purpose_title'          => 'Qu\'est-ce qui se discuté ?',
    'purpose_desc'           => 'Cela affecte seulement la formulation.',
    'purpose_motions'        => 'Motions',
    'purpose_manifesto'      => 'Programme',
    'purpose_manifesto_desc' => '&nbsp;',

    'language_title' => 'Langue du site',

    'single_mot_title' => 'Plusieurs motions vont-elles être discutées ?',
    'single_man_title' => 'Y a-t-il plusieurs chapitres / documents ?',
    'single_mot_desc'  => 'Un chapitre unique ne nécessite pas d\'aperçu des motions',
    'single_man_desc'  => 'Un chapitre unique ne nécessite pas une page d\'aperçu',
    'single_one'       => 'Seulement une',
    'single_multi'     => 'Plus qu\'une',

    'motwho_mot_title' => 'Qui peut déposer des motions ?',
    'motwho_man_title' => 'Qui peut déposer des chapitres ?',
    'motwho_admins'    => 'Admins',
    'motwho_loggedin'  => 'Utilisateurs enregistrés',
    'motwho_all'       => 'Tout le monde',

    'amendwho_title'    => 'Qui peut déposer des amendements ?',
    'amendwho_admins'   => 'Admins',
    'amendwho_loggedin' => 'Utilisateurs enregistrés',
    'amendwho_all'      => 'Tout le monde',

    'motdead_title'   => 'Y a-t-il une date limite pour les motions ?',
    'motdead_desc'    => 'La date-limite avant laquelle les motions doivent être déposées',
    'motdead_no'      => 'Non',
    'motdead_yes'     => 'Oui :',
    'amenddead_title' => 'Y a-t-il une date-limite pour les amendements ?',
    'amenddead_desc'  => 'La date-limite avant laquelle les amendements doivent être déposés',
    'amenddead_no'    => 'Non',
    'amenddead_yes'   => 'Oui :',

    'screening_mot_title'   => 'Examen préalable des nouvelles motions ?',
    'screening_man_title'   => 'Examen préalable des nouveaux chapitres ?',
    'screening_amend_title' => 'Examen préalable des nouveaux amendements ?',
    'screening_desc'        => 'Y a-t-il une phase d\'examen, de validation par les administrateurs avant que les textes soient publiés?',
    'screening_yes'         => 'Oui',
    'screening_no'          => 'Non',

    'supporters_title' => 'Demander des soutiens ?',
    'supporters_desc'  => 'L\'auteur doit-il entrer le nom de soutiens ?',
    'supporters_yes'   => 'Oui, au moins :',
    'supporters_no'    => 'Non',

    'amend_title' => 'Y a-t-il des amendements ?',
    'amend_no'    => 'Non',
    'amend_yes'   => 'Oui',

    'amend_singlepara_title'  => 'Un amendement peut-il affecter plusieurs passages ?',
    'amend_singlepara_desc'   => 'Si non, chaque amendement ne pourra proposer de modifier qu\'un seul paragraphe du document original.',
    'amend_singlepara_single' => 'Seulement un  passage',
    'amend_singlepara_multi'  => 'Passages multiples',

    'comments_title' => 'Commentaires aux motions ?',
    'comments_desc'  => 'Les utilisateurs sont-ils autorisés à commenter les motions et amendements?',
    'comments_no'    => 'Non',
    'comments_yes'   => 'Oui',

    'agenda_title' => 'Y a--il un ordre du jour formel ?',
    'agenda_desc'  => 'Si oui, il peut être précisé sur la page d\'accueil',
    'agenda_no'    => 'Non',
    'agenda_yes'   => 'Oui',

    'opennow_title' => 'Publier ce site immédiatement ?',
    'opennow_desc'  => 'Si non, il sera créé en mode de maintenance',
    'opennow_no'    => 'Créer en mode maintenance',
    'opennow_yes'   => 'Publier immédiatement',

    'sitedate_title'          => 'Presque fini !',
    'sitedate_desc'           => 'Il ne reste que quelques questions d\'organisation...',
    'sitedata_sitetitle'      => 'Nom du site / de la consultation',
    'sitedata_organization'   => 'Nom de l\'organisation de la consultation',
    'sitedata_subdomain'      => 'URL du site',
    'sitedata_subdomain_hint' => 'Seuls les lettres, les chiffres et "-" sont autorisés.',
    'sitedata_contact'        => 'Adresse de contact',
    'sitedata_contact_hint'   => 'Nom, email, adresse postale pour l\' imprint',
    'sitedata_subdomain_err'  => 'Le sous-domaine "%SUBDOMAIN%" n\'est pas disponible.',
    'sitedata_prettyurl'      => '"Jolies" URLs (a besoin d\'une réécriture de l\'URL)',
    'sitedata_system_email'   => 'Adresse email système',

    'created_title'          => 'Site créé',
    'created_msg'            => 'Le site a été créé.',
    'created_goto_con'       => 'Aller au nouveau site',
    'created_goto_motion'    => 'Tu peux maintenant créer des motions',
    'created_goto_manifesto' => 'Tu peux maintenant créer le programme',

    'sandbox_dummy_contact' => 'Test contact',
    'sandbox_dummy_orga'    => 'Organisation X',
    'sandbox_dummy_title'   => 'Evénement test',
    'sandbox_dummy_welcome' => '<h2>Bienvenue sur Antragsgrün!</h2><br><br>
                                Sur ce site bac-à-sable,tu peux tester librements toutes les fonctions d\'Antragsgrün.
                                Tout le monde peut accéder à ce site en utilisation ce lien:<br><br>
                                <blockquote><strong><a href="%SITE_URL%">%SITE_URL%</a></strong></blockquote>
                                <br><br>
                                Nous avons créé un utilisateur admin pour que tu puisses tester toutes les fonctions administratives:<br><br>
                                <blockquote>
                                <strong>Login:</strong> %ADMIN_USERNAME%<br>
                                <strong>Mot de passe:</strong> %ADMIN_PASSWORD%
                                </blockquote>
                                <br><br>
                                Toutefois, merci de noter que ce site sera <strong>supprimé après trois jours</strong>.<br><br>
                                <em>Au fait, tu peux modifier ce texte en cliquant sur le bouton "Modifier" en haut à droite.</em>',

    'cons_err_fields_missing' => 'Certains champs sont manquants.',
    'cons_err_path_taken'     => 'Cette adresse est déjà prise par une autre consultation sur ce site.',
];
