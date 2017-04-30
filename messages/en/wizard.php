<?php

return [
    'title' => 'Create a site',

    'step_purpose'    => 'Purpose',
    'step_motions'    => 'Motions',
    'step_amendments' => 'Amendments',
    'step_special'    => 'Special cases',
    'step_site'       => 'Organizational issues',

    'next'   => 'Next',
    'prev'   => 'Back',
    'finish' => 'Ready / Create site',

    'purpose_title'          => 'What will be discussed?',
    'purpose_desc'           => 'This only affects the wording.',
    'purpose_motions'        => 'Motions',
    'purpose_manifesto'      => 'Manifesto',
    'purpose_manifesto_desc' => '&nbsp;',

    'single_mot_title' => 'Will multiple motions be discussed?',
    'single_man_title' => 'Are there multiple chapters / documents?',
    'single_mot_desc'  => 'A single chapter requires no motion overview',
    'single_man_desc'  => 'A single chapter requires no overview page',
    'single_one'       => 'Only one',
    'single_multi'     => 'More than one',

    'motwho_mot_title' => 'Who can submit motions?',
    'motwho_man_title' => 'Who can submit chapters?',
    'motwho_admins'    => 'Admins',
    'motwho_loggedin'  => 'Registered Users',
    'motwho_all'       => 'Everyone',

    'amendwho_title'    => 'Who can submit amendments?',
    'amendwho_admins'   => 'Admins',
    'amendwho_loggedin' => 'Registered users',
    'amendwho_all'      => 'Everyone',

    'motdead_title'   => 'Is there a deadline for motions?',
    'motdead_desc'    => 'The deadline by which motions can be submitted',
    'motdead_no'      => 'No',
    'motdead_yes'     => 'Yes:',
    'amenddead_title' => 'Is there a deadline for amendments?',
    'amenddead_desc'  => 'The deadline by which amendments can be submitted',
    'amenddead_no'    => 'No',
    'amenddead_yes'   => 'Yes:',

    'screening_mot_title'   => 'Pre-screening of new motions?',
    'screening_man_title'   => 'Pre-screening of new chapters?',
    'screening_amend_title' => 'Pre-screening of new amendments?',
    'screening_desc'        => 'Is a screening process needed before public publishing?',
    'screening_yes'         => 'Yes',
    'screening_no'          => 'No',

    'supporters_title' => 'Ask for supporters?',
    'supporters_desc'  => 'Does the proposer have to enter the names of supporters?',
    'supporters_yes'   => 'Yes, at least:',
    'supporters_no'    => 'No',

    'amend_title' => 'Are there amendments?',
    'amend_no'    => 'No',
    'amend_yes'   => 'Yes',

    'amend_singlepara_title'  => 'Can an amendment affect multiple passages?',
    'amend_singlepara_desc'   => 'If not, then an amendment may only propose to change one specific paragraph of the original document.',
    'amend_singlepara_single' => 'Only one passage',
    'amend_singlepara_multi'  => 'Multiple passages',

    'amend_merging_title'  => 'Adopting amendments',
    'amend_merging_desc'   => 'Are proposers of motions allowed to adopt amendments by themselves?',
    'amend_merging_yes'    => 'Yes',
    'amend_merging_no'     => 'No',

    'comments_title' => 'Comments to motions?',
    'comments_desc'  => 'Are users allowed to comment on motions and amendments?',
    'comments_no'    => 'No',
    'comments_yes'   => 'Yes',

    'agenda_title' => 'Is there a formal agenda?',
    'agenda_desc'  => 'If so, it can be specified on the home page',
    'agenda_no'    => 'No',
    'agenda_yes'   => 'Yes',

    'opennow_title' => 'Publish this site immediately?',
    'opennow_desc'  => 'If not, it will be created in maintenance mode',
    'opennow_no'    => 'Start in maintenance mode',
    'opennow_yes'   => 'Start immediately',

    'sitedate_title'          => 'Almost finished!',
    'sitedate_desc'           => 'Just some more organization questions...',
    'sitedata_sitetitle'      => 'Name of this site / this consultation',
    'sitedata_organization'   => 'Name of the organization of this consultation',
    'sitedata_subdomain'      => 'Site URL',
    'sitedata_subdomain_hint' => 'Only letters, numbers, "_" and "-" are allowed.',
    'sitedata_contact'        => 'Contact address',
    'sitedata_contact_hint'   => 'Name, e-mail, postal address for the imprint',
    'sitedata_subdomain_err'  => 'The subdomain "%SUBDOMAIN%" is not available.',
    'sitedata_prettyurl'      => '"Pretty" URLs (needs URL-rewriting)',
    'sitedata_system_email'   => 'System e-mail-address',

    'created_title'          => 'Site created',
    'created_msg'            => 'The site has been created.',
    'created_goto_con'       => 'Go to the new site',
    'created_goto_motion'    => 'Now you can create the motion',
    'created_goto_manifesto' => 'Now you can create the manifesto',

    'sandbox_dummy_contact' => 'Test contact',
    'sandbox_dummy_orga'    => 'Organiszation X',
    'sandbox_dummy_title'   => 'Test event',
    'sandbox_dummy_welcome' => '<h2>Welcome to Antragsgrün!</h2><br><br>
                                On this sandbox site, you can freely test all Antragsgrün features.
                                Everyone can access this site using this URL:<br><br>
                                <blockquote><strong><a href="%SITE_URL%">%SITE_URL%</a></strong></blockquote>
                                <br><br>
                                We\'ve created a dummy admin user for you to test all administrative tasks:<br><br>
                                <blockquote>
                                <strong>Login:</strong> %ADMIN_USERNAME%<br>
                                <strong>Password:</strong> %ADMIN_PASSWORD%
                                </blockquote>
                                <br><br>
                                However, please note that this site will be <strong>deleted after three days</strong>.<br><br>
                                <em>By the way: you can edit this text by clicking on "edit" on the top right.</em>',

    'cons_err_fields_missing' => 'Some fields are missing.',
    'cons_err_path_taken'     => 'This path is already taken by another consultation on this site.',
];
