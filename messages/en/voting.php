<?php

declare(strict_types=1);

return [
    'votings_bc' => 'Votings',
    'results_bc' => 'Results',
    'admin_bc' => 'Administration',

    'sidebar_open' => 'Open votings',
    'sidebar_results' => 'Results',
    'sidebar_admin' => 'Administration',

    'title_user_single' => 'Voting',

    'vote_yes' => 'Yes',
    'vote_no' => 'No',
    'vote_abstention' => 'Abstention',
    'vote_present' => 'Present',
    'vote_undo' => 'Undo vote',
    'vote_abstain' => [
        'text' => 'General Abstention',
        'js' => true,
    ],

    'status_accepted' => [
        'text' => 'Accepted',
        'js' => true,
    ],
    'status_rejected' => [
        'text' => 'Rejected',
        'js' => true,
    ],
    'status_quorum_missed' => [
        'text' => 'Quorum missed',
        'js' => true,
    ],
    'status_quorum_reached' => [
        'text' => 'Quorum reached',
        'js' => true,
    ],

    'activity_title' => 'Protocol',
    'activity_show_all' => [
        'text' => 'Show complete protocol',
        'js' => true,
    ],
    'activity_opened' => [
        'text' => 'Voting opened',
        'js' => true,
    ],
    'activity_closed' => [
        'text' => 'Voting closed',
        'js' => true,
    ],
    'activity_reset' => [
        'text' => 'Voting reset',
        'js' => true,
    ],
    'activity_reopened' => [
        'text' => 'Voting re-opened',
        'js' => true,
    ],

    'page_title' => 'Votings',
    'results_title' => 'Voting results',
    'results_none' => 'No voting results have been published yet',
    'results_download' => [
        'text' => 'Results as Spreadsheet',
        'js' => true,
    ],
    'votings_none' => 'No votings are open',
    'remaining_time' => [
        'text' => 'Voting time',
        'js' => true,
    ],

    'admin_title' => 'Voting administration',
    'admin_intro' => '<strong>Hint:</strong> you can find a manual for the voting functionality on the <a href="https://sandbox.motion.tools/help#advanced">help page</a>.',
    'admin_aria_single' => 'Administrate voting: %TITLE%',
    'admin_voting_use' => [
        'text' => 'Online voting',
        'js' => true,
    ],
    'admin_voting_use_h' => [
        'text' => 'The voting about the following motions and amendments shall take place online on Antragsgrün',
        'js' => true,
    ],
    'admin_votes_total' => [
        'text' => 'Total',
        'js' => true,
    ],
    'admin_btn_open' => [
        'text' => 'Open voting',
        'js' => true,
    ],
    'admin_btn_close' => [
        'text' => 'Close voting',
        'js' => true,
    ],
    'admin_btn_close_op' => [
        'text' => 'Close: show more options',
        'js' => true,
    ],
    'admin_btn_close_pub' => [
        'text' => 'Close and publish (default)',
        'js' => true,
    ],
    'admin_btn_close_nopub' => [
        'text' => 'Close without publishing results',
        'js' => true,
    ],
    'admin_btn_cancel' => 'Cancel',
    'admin_btn_reset' => [
        'text' => 'Reset',
        'js' => true,
    ],
    'admin_btn_reset_bb' => 'This will remove all votes and set the voting back to preparation mode, where you can add or remove motions and amendments. WARNING: All users will have to vote again.',
    'admin_btn_reopen' => [
        'text' => 'Re-Open',
        'js' => true,
    ],
    'admin_btn_publish' => [
        'text' => 'Publish results',
        'js' => true,
    ],
    'admin_btn_remove_item' => 'Remove from voting',
    'admin_status_opened' => [
        'text' => 'The voting is <strong>open</strong>, users can now cast their votes',
        'js' => true,
    ],
    'admin_status_closed' => [
        'text' => 'The voting is <strong>closed</strong>, the results <strong>published</strong>.',
        'js' => true,
    ],
    'admin_status_closed_unpublished' => [
        'text' => 'This voting is <strong>closed</strong>, but the results are <strong>not yet published</strong>.',
        'js' => true,
    ],
    'admin_members_present' => 'Members present',
    'admin_no_items_yet' => [
        'text' => 'No motion, amendment or voting item has been added to this voting yet.',
        'js' => true,
    ],
    'admin_add_amendments' => [
        'text' => 'Add a motion or amendment',
        'js' => true,
    ],
    'admin_add_amendments_opt' => [
        'text' => 'Please select a motion/amendment to add',
        'js' => true,
    ],
    'admin_add_question' => [
        'text' => 'Add a voting item',
        'js' => true,
    ],
    'admin_add_question_title' => [
        'text' => 'Voting item',
        'js' => true,
    ],
    'admin_add_btn' => [
        'text' => 'Add selected motion or amendment',
        'js' => true,
    ],
    'admin_add_opt_motion' => [
        'text' => 'Add the motion',
        'js' => true,
    ],
    'admin_add_opt_all_amend' => [
        'text' => 'Add all following amendments',
        'js' => true,
    ],
    'admin_add_abort' => 'Abort adding',
    'admin_settings_open' => [
        'text' => 'Show settings',
        'js' => true,
    ],
    'admin_settings_close' => [
        'text' => 'Close settings',
        'js' => true,
    ],
    'admin_mvtoug_caller' => [
        'text' => 'Assign voters to a user group',
        'js' => true,
    ],
    'admin_reset_dialog' => [
        'text' => 'Are you sure you want to reset the whole voting?',
        'js' => true,
    ],

    'settings_create' => 'New voting',
    'settings_sort' => 'Sort',
    'settings_open' => 'Show settings',
    'settings_close' => 'Hide settings',
    'settings_votingtype' => 'What is voted?',
    'settings_votingtype_motion' => 'Motions and/or amendments',
    'settings_votingtype_question' => 'A specific voting item',
    'settings_title' => [
        'text' => 'Title',
        'js' => true,
    ],
    'settings_question' => 'First voting item',
    'settings_answers' => [
        'text' => 'Answer options',
        'js' => true,
    ],
    'settings_answers_yesnoabst' => [
        'text' => 'Yes, No, Abstention',
        'js' => true,
    ],
    'settings_answers_yesno' => [
        'text' => 'Yes, No',
        'js' => true,
    ],
    'settings_answers_yes' => [
        'text' => 'Yes',
        'js' => true,
    ],
    'settings_answers_yesh' => [
        'text' => 'For lists. After creation, the "Number of votes per user" can be given in the settings.',
        'js' => true,
    ],
    'settings_answers_present' => [
        'text' => 'Present',
        'js' => true,
    ],
    'settings_answers_presenth' => [
        'text' => 'For "votings"”" meant to ask which members are present, like roll calls.',
        'js' => true,
    ],
    'settings_majoritytype' => [
        'text' => 'Majority type',
        'js' => true,
    ],
    'settings_quorumtype' => [
        'text' => 'Quorum',
        'js' => true,
    ],
    'settings_generalabstention' => [
        'text' => 'Allow explicit general abstention',
        'js' => true,
    ],
    'settings_votepolicy' => [
        'text' => 'Who may vote',
        'js' => true,
    ],
    'settings_resultspublic' => [
        'text' => 'Who may see the voting results',
        'js' => true,
    ],
    'settings_resultspublic_admins' => [
        'text' => 'Admins',
        'js' => true,
    ],
    'settings_resultspublic_all' => [
        'text' => 'Everyone',
        'js' => true,
    ],
    'settings_votespublic' => [
        'text' => 'Who may see who voted how',
        'js' => true,
    ],
    'settings_votespublic_hint' => [
        'text' => 'This setting can only be changed until the voting has been opened.',
        'js' => true,
    ],
    'settings_votespublic_nobody' => [
        'text' => 'Nobody',
        'js' => true,
    ],
    'settings_votespublic_admins' => [
        'text' => 'Admins',
        'js' => true,
    ],
    'settings_votespublic_all' => [
        'text' => 'Everyone',
        'js' => true,
    ],
    'settings_votesnames' => [
        'text' => 'Which name of the voter shall be shown?',
        'js' => true,
    ],
    'settings_votesnames_auth' => [
        'text' => 'Username (e.g. e-mail)',
        'js' => true,
    ],
    'settings_votesnames_name' => [
        'text' => 'Name',
        'js' => true,
    ],
    'settings_votesnames_organization' => [
        'text' => 'Organization (if given)',
        'js' => true,
    ],
    'settings_maxvotes' => [
        'text' => 'Number of votes per user',
        'js' => true,
    ],
    'settings_maxvotes_h' => [
        'text' => 'This can be used, for example, to present 7 candidates, and only allow up to 3 votes for each user',
        'js' => true,
    ],
    'settings_maxvotes_none' => [
        'text' => 'Unlimited',
        'js' => true,
    ],
    'settings_maxvotes_limit' => [
        'text' => 'Limited votes',
        'js' => true,
    ],
    'settings_maxvotes_pergroup' => [
        'text' => 'Depending on user group',
        'js' => true,
    ],
    'settings_maxvotes_votes' => [
        'text' => 'Votes',
        'js' => true,
    ],
    'settings_motionassign' => [
        'text' => 'Assigned to motion',
        'js' => true,
    ],
    'settings_motionassign_h' => [
        'text' => 'If this voting is assigned to a motion, it will be shown on the motion page, not on the home page',
        'js' => true,
    ],
    'settings_motionassign_none' => [
        'text' => 'None',
        'js' => true,
    ],
    'settings_timer' => [
        'text' => 'Time for voting',
        'js' => true,
    ],
    'settings_timer_h' => [
        'text' => 'If a number of seconds is set, a countdown appears when the voting is open. This is just informal, though - the voting still needs to be explicitly closed manually.',
        'js' => true,
    ],
    'settings_timer_sec' => [
        'text' => 'Seconds',
        'js' => true,
    ],
    'settings_save' => [
        'text' => 'Save',
        'js' => true,
    ],
    'settings_delete' => [
        'text' => 'Delete the voting',
        'js' => true,
    ],
    'settings_delete_bb' => [
        'text' => 'Do you want to delete the voting including all cast votes? The motions and amendments will remain untouched.',
        'js' => true,
    ],
    'settings_sort_title' => [
        'text' => 'Reorder votings',
        'js' => true,
    ],
    'settings_sort_save' => [
        'text' => 'Save new order',
        'js' => true,
    ],

    'voting_current_aria' => 'Currently active voting',
    'voting_show_amend' => 'Show amendment',
    'voting_edit_amend' => 'Edit amendment',
    'voting_by' => [
        'text' => 'By %BY%',
        'js' => true,
    ],
    'voting_admin_all' => [
        'text' => 'Administrate votings',
        'js' => true,
    ],
    'voting_visibility' => [
        'text' => 'Who can see how I voted?',
        'js' => true,
    ],
    'voting_visibility_none' => [
        'text' => 'Nobody can see how you voted. <small>(Persons with access to the database could access this data, though)</small>',
        'js' => true,
    ],
    'voting_visibility_admin' => [
        'text' => 'The votes are visible to the <strong>administrators</strong> of this page, but not for other participants.',
        'js' => true,
    ],
    'voting_visibility_all' => [
        'text' => '<strong>All logged in users</strong> can see who voted how.',
        'js' => true,
    ],
    'voting_show_all' => 'Show all votings',
    'voting_votes_status' => [
        'text' => 'Status',
        'js' => true,
    ],
    'voting_votes_0' => [
        'text' => 'No vote has been cast yet.',
        'js' => true,
    ],
    'voting_votes_1_1' => [
        'text' => '1 vote has been cast.',
        'js' => true,
    ],
    'voting_votes_1_x' => '%VOTES% votes have been cast by 1 user.',
    'voting_votes_x' => '%VOTES% votes have been cast by %USERS% users.',
    'voting_votes_x_same' => '%VOTES% votes have been cast.',
    'voting_remainig_0' => [
        'text' => 'You have cast all your votes.',
        'js' => true,
    ],
    'voting_remainig_1' => [
        'text' => 'You have 1 remaining vote to cast.',
        'js' => true,
    ],
    'voting_remainig_x' => [
        'text' => 'You have %VOTES% remaining votes to cast.',
        'js' => true,
    ],
    'voting_notvoted' => [
        'text' => 'Not voted',
        'js' => true,
    ],
    'voting_notvoted_yet' => [
        'text' => 'Not voted yet',
        'js' => true,
    ],
    'voting_notvoted_0' => [
        'text' => 'None',
        'js' => true,
    ],
    'voting_presence_0' => [
        'text' => 'Nobody has marked their presence yet',
        'js' => true,
    ],
    'voting_presence_1_1' => [
        'text' => '1 user has marked their presence',
        'js' => true,
    ],
    'voting_presence_1_x' => '%VOTES% presences have been marked by 1 user',
    'voting_presence_x' => '%VOTES% presences have been marked by %USERS% users',
    'voting_presence_x_same' => '%VOTES% presences have been marked',
    'voting_weight' => [
        'text' => 'Voting weight',
        'js' => true,
    ],
    'voting_show_votes' => [
        'text' => 'Show vote list',
        'js' => true,
    ],
    'voting_hide_votes' => [
        'text' => 'Hide vote list',
        'js' => true,
    ],
    'voting_abstentions_1' => '1 General Abstention',
    'voting_abstentions_x' => '%NUM% General Abstentions',

    'majority_simple' => 'Simple majority',
    'majority_simple_h' => 'A motion or amendment is adopted, if more yes- than no-votes are cast. Abstentions are not counted.',
    'majority_absolute' => 'Absolute majority',
    'majority_absolute_h' => 'A motion or amendment is adopted, if more yes- than no-votes and abstentions combined are cast.',
    'majority_twothirds' => '2/3 majority',
    'majority_twothirds_h' => 'A motion or amendment is adopted, if at least (including) twice as many yes- as no-votes are cast. Abstentions are not counted.',

    'quorum_none' => 'No quorum',
    'quorum_half' => 'Simple majority',
    'quorum_half_h' => 'At least half of all eligible users have to cast a vote',
    'quorum_two_third' => '2/3 majority',
    'quorum_two_third_h' => 'At least two out of three of all eligible users have to cast a vote',
    'quorum_limit' => '%QUORUM% out of %ALL% users',
    'quorum_counter' => [
        'text' => 'Quorum: %CURRENT% out of %QUORUM% necessary votes',
        'js' => true,
    ],
];


