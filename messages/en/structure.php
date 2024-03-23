<?php
return [
    'section_title'           => 'Title',
    'section_text'            => 'Text',
    'section_html'            => 'Text (enhanced)',
    'section_editorial'       => 'Editorial text',
    'section_image'           => 'Image',
    'section_tabular'         => 'Tabular data',
    'section_pdf_attachment'  => 'PDF attachment',
    'section_pdf_alternative' => 'PDF alternative',
    'section_video_embed'     => 'Embedded video',

    'amend_number_permotion' => 'Ä1 for M1 (Numbering per motion)',
    'amend_number_global'    => 'Ä1 for M1 (Global numbering)',
    'amend_number_english'   => 'M1 A1 (Numbering per motion)',
    'amend_number_perline'   => 'M01-070 (Numbering per affected line)',

    'policy_deadline_over'       => 'The deadline is over',
    'policy_deadline_over_comm'  => 'No comments are possible at this point of time.',
    'policy_deadline_over_merge' => 'Merging is not possible at this point of time.',
    'policy_deadline_from'       => 'starting %from%',
    'policy_deadline_to'         => 'ending %to%',
    'policy_deadline_from_to'    => 'from %from% to %to%',
    'policy_deadline_na'         => 'unlimited',
    'policy_ww_title'            => 'Grünes-Netz-Users',
    'policy_ww_desc'             => 'Grünes-Netz-Users',
    'policy_ww_motion_denied'    => 'Only Grünes-Netz-users can create motions.',
    'policy_ww_amend_denied'     => 'Only Grünes-Netz-users can create amendments.',
    'policy_ww_comm_denied'      => 'Only Grünes-Netz-users can comment',
    'policy_ww_supp_denied'      => 'Only Grünes-Netz-users can support',

    'policy_admin_title'         => 'Admins',
    'policy_admin_desc'          => 'Admins',
    'policy_admin_motion_denied' => 'Only admins can create motions.',
    'policy_admin_amend_denied'  => 'Only admins can create amendments.',
    'policy_admin_comm_denied'   => 'Only admins can comment',
    'policy_admin_supp_denied'   => 'Only admins can support',

    'policy_all_title'            => 'Everyone',
    'policy_all_desc'             => 'Everyone',
    'policy_nobody_title'         => 'Nobody',
    'policy_nobody_desc'          => 'Amendments are deactivated. This page is only accessible because you are admin.',
    'policy_nobody_motion_denied' => 'Currently nobody can create motions.',
    'policy_nobody_amend_denied'  => 'Currently nobody can create amendments.',
    'policy_nobody_comm_denied'   => 'Currently nobody can comment.',
    'policy_nobody_supp_denied'   => 'Currently nobody can support.',

    'policy_logged_title'           => 'Registered users',
    'policy_logged_desc'            => 'Registered users',
    'policy_specuser_motion_denied' => 'Only explicitly entitled users can create motions.',
    'policy_specuser_amend_denied'  => 'Only explicitly entitled users can create amendments.',
    'policy_specuser_supp_denied'   => 'Only explicitly entitled users can support.',
    'policy_specuser_comm_denied'   => 'Only explicitly entitled users can comment.',
    'policy_logged_motion_denied'   => 'You need to log in to create motions.',
    'policy_logged_amend_denied'    => 'You need to log in to create amendments.',
    'policy_logged_supp_denied'     => 'You need to log in to support.',
    'policy_logged_comm_denied'     => 'You need to log in to comment.',

    'policy_groups_title'           => 'Selected groups',
    'policy_groups_desc'            => 'Entitled user groups',
    'policy_groups_motion_denied'   => 'Only specified user groups can create motions.',
    'policy_groups_amend_denied'    => 'Only specified user groups can create amendments.',
    'policy_groups_supp_denied'     => 'Only specified user groups can support motions.',
    'policy_groups_comm_denied'     => 'Only specified user groups can write comments.',

    'privilege_consettings'   => 'Manage consultation settings',
    'privilege_content'       => 'Manage content pages / texts',
    'privilege_speech'        => 'Manage speech queues',
    'privilege_voting'        => 'Manage votings',
    'privilege_screening'     => 'Screening',
    'privilege_motionsee'     => 'See all (including unpublished)',
    'privilege_motionstruct'  => 'Manage metadata',
    'privilege_motioncontent' => 'Manage content',
    'privilege_motionusers'   => 'Manage initiators',
    'privilege_motiondelete'  => 'Delete',
    'privilege_proposals'     => 'Manage proposed procedure',

    'preset_bdk_name'           => 'German federal convention',
    'preset_bdk_desc'           => 'Presets similar to the federal convention of the German Green Party. This setting does not make sense outside of Germany.',
    'preset_election_name'      => 'Application / elections',
    'preset_election_desc'      => '',
    'preset_motions_name'       => 'Motions',
    'preset_motions_desc'       => 'Everyone can create motions and amendments. They need to be screened by conference administrators, though.',
    'preset_party_name'         => 'Political convention',
    'preset_party_desc'         => 'A convention with a sample agenda. Users can apply for mandates and create motions and amendments. The agenda and mandates can be adjusted deliberately.',
    'preset_party_top'          => 'Agenda',
    'preset_party_elections'    => 'Elections',
    'preset_party_1leader'      => 'Election: 1st chairperson',
    'preset_party_2leader'      => 'Election: 2nd chairperson',
    'preset_party_treasure'     => 'Election: treasurer',
    'preset_party_motions'      => 'Motions',
    'preset_party_misc'         => 'Miscellaneous',
    'preset_manifesto_name'     => 'Election programme', // @TODO
    'preset_manifesto_desc'     => 'A draft version of the programme is published by administrators. Users can comment on the drafts and create amendments. The latter have to be screened before they are published. Everyone can comment.',
    'preset_manifesto_title'    => 'Chapter title',
    'preset_manifesto_text'     => 'Text',
    'preset_manifesto_singular' => 'Chapter',
    'preset_manifesto_plural'   => 'Chapters',
    'preset_manifesto_call'     => 'Create a chapter',

    'preset_app_singular'  => 'Application',
    'preset_app_plural'    => 'Applications',
    'preset_app_call'      => 'Apply',
    'preset_app_name'      => 'Name',
    'preset_app_photo'     => 'Photo',
    'preset_app_pdf'       => 'PDF',
    'preset_app_data'      => 'Data',
    'preset_app_signature' => 'Signature (scanned)',
    'preset_app_age'       => 'Age',
    'preset_app_gender'    => 'Gender',
    'preset_app_birthcity' => 'Place of birth',
    'preset_app_intro'     => 'Introduction',
    'preset_app_title_int' => 'Application: ',

    'preset_motion_singular' => 'Motion',
    'preset_motion_plural'   => 'Motions',
    'preset_motion_call'     => 'Start a motion',
    'preset_motion_title'    => 'Title',
    'preset_motion_text'     => 'Motion text',
    'preset_motion_reason'   => 'Reason',

    'preset_statutes_singular' => 'Statutes amendment',
    'preset_statutes_plural'   => 'Statutes amendments',
    'preset_statutes_call'     => 'Statutes amendment',
    'preset_statutes_title'    => 'Title',
    'preset_statutes_text'     => 'Statutes',

    'role_initiator' => 'Proposer',
    'role_supporter' => 'Supporter',
    'role_likes'     => 'Likes',
    'role_dislikes'  => 'Dislikes',

    'person_type_natural' => 'Natural person',
    'person_type_orga'    => 'Organization',

    'user_status_1'  => 'Unconfirmed',
    'user_status_0'  => 'Confirmed',
    'user_status_-1' => 'Deleted',

    'dateformat_default' => 'Default (language-dependent)',
    'dateformat_dmy_dot' => 'dd.mm.yyyy (e.g. Germany)',
    'dateformat_dmy_slash' => 'dd/mm/yyyy (e.g. France)',
    'dateformat_mdy_slash' => 'mm/dd/yyyy (e.g. USA)',
    'dateformat_ymd_dash' => 'yyyy-mm-dd (int. standard)',
    'dateformat_dmy_dash' => 'dd-mm-yyyy (e.g. the Netherlands)',

    'STATUS_DELETED'                      => 'Deleted',
    'STATUSV_DELETED'                     => 'Delete',
    'STATUS_WITHDRAWN'                    => 'Withdrawn',
    'STATUSV_WITHDRAWN'                   => 'Withdraw',
    'STATUS_WITHDRAWN_INVISIBLE'          => 'Withdrawn (invisible)',
    'STATUS_UNCONFIRMED'                  => 'Unconfirmed',
    'STATUS_DRAFT'                        => 'Draft',
    'STATUS_SUBMITTED_UNSCREENED'         => 'Submitted (unscreened)',
    'STATUS_SUBMITTED_SCREENED'           => 'Submitted',
    'STATUS_ACCEPTED'                     => 'Accepted',
    'STATUSV_ACCEPTED'                    => 'Accept',
    'STATUS_REJECTED'                     => 'Rejected',
    'STATUSV_REJECTED'                    => 'Reject',
    'STATUS_QUORUM_MISSED'                => 'Quorum missed',
    'STATUS_QUORUM_REACHED'               => 'Quorum reached',
    'STATUS_MODIFIED_ACCEPTED'            => 'Accepted (modified)',
    'STATUSV_MODIFIED_ACCEPTED'           => 'Accept (modified)',
    'STATUS_MODIFIED'                     => 'Modified',
    'STATUSV_MODIFIED'                    => 'Modify',
    'STATUS_ADOPTED'                      => 'Adopted',
    'STATUSV_ADOPTED'                     => 'Adopt',
    'STATUS_COMPLETED'                    => 'Completed',
    'STATUS_REFERRED'                     => 'Referred',
    'STATUSV_REFERRED'                    => 'Refer',
    'STATUS_VOTE'                         => 'Vote',
    'STATUSV_VOTE'                        => 'Vote',
    'STATUS_PAUSED'                       => 'Paused',
    'STATUS_MISSING_INFORMATION'          => 'Missing information',
    'STATUS_DISMISSED'                    => 'Dismissed',
    'STATUS_COLLECTING_SUPPORTERS'        => 'Call for supporters',
    'STATUS_DRAFT_ADMIN'                  => 'Draft (Admin)',
    'STATUS_PROCESSED'                    => 'Processed',
    'STATUS_SUBMITTED_UNSCREENED_CHECKED' => 'Submitted (screened, not yet published)',
    'STATUS_PROPOSED_MODIFIED_AMENDMENT'  => 'Proposed modification (amend)',
    'STATUS_PROPOSED_MODIFIED_MOTION'     => 'Proposed modification (motion)',
    'STATUS_STATUS_PROPOSED_MOVE_TO_OTHER_MOTION' => 'Proposed move from other motion',
    'STATUS_OBSOLETED_BY_AMEND'           => 'Handled by another amendment',
    'STATUS_OBSOLETED_BY_MOTION'          => 'Handled by another motion',
    'STATUS_CUSTOM_STRING'                => 'Custom status',
    'STATUS_INLINE_REPLY'                 => 'Response',
    'STATUS_MERGING_DRAFT_PUBLIC'         => 'Merging Draft (public)',
    'STATUS_MERGING_DRAFT_PRIVATE'        => 'Merging Draft (invisible)',
    'STATUS_RESOLUTION_PRELIMINARY'       => 'Resolution (preliminary)',
    'STATUS_RESOLUTION_FINAL'             => 'Resolution',
    'STATUS_MOVED'                        => 'Moved',

    'PROPOSED_ACCEPTED_MOTION'    => 'Accepted',
    'PROPOSED_ACCEPTED_AMEND'     => 'Accepted',
    'PROPOSED_REJECTED'           => 'Rejected',
    'PROPOSED_MODIFIED_ACCEPTED'  => 'Accepted (modified)',
    'PROPOSED_REFERRED'           => 'Referred',
    'PROPOSED_VOTE'               => 'Vote',
    'PROPOSED_OBSOLETED_BY_AMEND' => 'Handled by another amendment',
    'PROPOSED_OBSOLETED_BY_MOT'   => 'Handled by another motion',
    'PROPOSED_CUSTOM_STRING'      => 'Custom status',
    'PROPOSED_MOVE_TO_OTHER_MOTION' => 'Moved to other motion',

    'section_comment_none'      => 'No comments',
    'section_comment_motion'    => 'Comment the whole motion as one',
    'section_comment_paragraph' => 'Comment single paragraphs',

    'home_layout_std'               => 'No agenda',
    'home_layout_tags'              => 'Tags / categories',
    'home_layout_agenda'            => 'Motions inlined into the agenda',
    'home_layout_agenda_long'       => 'Motions are below the agenda',
    'home_layout_agenda_hide_amend' => 'Motions are below the agenda, amendments hidden',
    'home_layout_discussion_tags'   => 'Comments above motion list, tags / categories with filter',

    'motiondata_all'  => 'Show all (incl. date, status and consultation)',
    'motiondata_mini' => 'Short version (only proposer, topic etc.)',
    'motiondata_none' => 'Don\'t show',

    'supp_only_initiators'    => 'Only proposer',
    'supp_given_by_initiator' => 'Supporters given by proposer',
    'supp_collect_before'     => 'Collecting phase before publication (not for organizations)',
    'supp_no_initiator'       => 'No proposer',

    'activity_none'                       => 'No activity yet',
    'activity_someone'                    => 'Someone',
    'activity_deleted'                    => 'deleted',
    'activity_MOTION_DELETE'              => '###USER### <strong>deleted the motion ###MOTION###</strong>',
    'activity_MOTION_DELETE_PUBLISHED'    => '###USER### <strong>deleted the motion ###MOTION###</strong>',
    'activity_MOTION_PUBLISH'             => '###USER### <strong>submitted the motion</strong>',
    'activity_MOTION_CHANGE'              => '###USER###  <strong>edited the motion ###MOTION###</strong>',
    'activity_MOTION_WITHDRAW'            => 'The motion was <strong>withdrawn</strong>.',
    'activity_MOTION_COMMENT'             => '###USER### <strong>commented on the motion</strong>',
    'activity_MOTION_SCREEN'              => 'The <strong>motion was published</strong>',
    'activity_MOTION_PUBLISH_PROPOSAL'    => '###USER### <strong>published the proposed procedure</strong>',
    'activity_MOTION_SET_PROPOSAL'        => '###USER### <strong>edited the proposed procedure</strong>',
    'activity_MOTION_SUPPORT'             => '###USER### <strong>supported the motion</strong>',
    'activity_AMENDMENT_PUBLISH'          => '###USER### <strong>submitted the amendment ###AMENDMENT###</strong>',
    'activity_AMENDMENT_CHANGE'           => '###USER### <strong>edited the amendment ###AMENDMENT###</strong>',
    'activity_AMENDMENT_DELETE'           => '###USER### <strong>deleted the amendment ###AMENDMENT###</strong>',
    'activity_AMENDMENT_DELETE_PUBLISHED' => '###USER### <strong>deleted the amendment ###AMENDMENT###</strong>',
    'activity_AMENDMENT_WITHDRAW'         => 'The <strong>amendment ###AMENDMENT###</strong> was <strong>withdrawn</strong>.',
    'activity_AMENDMENT_COMMENT'          => '###USER### <strong>commented on the amendment ###AMENDMENT###</strong>',
    'activity_AMENDMENT_SCREEN'           => 'The <strong>amendment ###AMENDMENT###</strong> was <strong>published</strong>',
    'activity_AMENDMENT_SUPPORT'          => '###USER### <strong>supported the amendment ###AMENDMENT###</strong>',
    'activity_AMENDMENT_PUBLISH_PROPOSAL' => '###USER### <strong>published the proposed procedure of ###AMENDMENT###</strong>',
    'activity_AMENDMENT_SET_PROPOSAL'     => '###USER### <strong>edited the proposed procedure of ###AMENDMENT###</strong>',
    'activity_AMENDMENT_VOTE_REJECTED'    => 'The <strong>amendment ###AMENDMENT###</strong> was <strong>rejected</strong>.',
    'activity_AMENDMENT_VOTE_ACCEPTED'    => 'The <strong>amendment ###AMENDMENT###</strong> was <strong>accepted</strong>.',
    'activity_VOTING_OPEN'                => 'The voting was <strong>opened</strong>',
    'activity_VOTING_CLOSE'               => 'The voting was <strong>closed</strong>',
    'activity_VOTING_DELETE'              => 'The voting was <strong>deleted</strong>',
    'activity_VOTING_QUESTION_ACCEPTED'   => 'The question was <strong>accepted</strong>',
    'activity_VOTING_QUESTION_REJECTED'   => 'The question was <strong>rejected</strong>',
    'activity_USER_ADD_TO_GROUP'          => '###USER### was added to the group "###GROUP###".',
    'activity_USER_REMOVE_FROM_GROUP'     => '###USER### was removed from the group "###GROUP###".',

    'remaining_over'    => 'over',
    'remaining_days'    => 'days',
    'remaining_day'     => 'day',
    'remaining_hours'   => 'hours',
    'remaining_hour'    => 'hour',
    'remaining_minutes' => 'minutes',
    'remaining_minute'  => 'minute',
    'remaining_seconds' => 'seconds',
    'remaining_second'  => 'second',

    'robots_policy_none'      => 'No pages at all',
    'robots_policy_only_home' => 'Only the home page, no motions',
    'robots_policy_all'       => 'All pages accessible by not-logged-in users',

    'gender_na'      => '-',
    'gender_female'  => 'Female',
    'gender_male'    => 'Male',
    'gender_diverse' => 'Inter/Diverse',

    'top_separator' => [
        'text'        => '.',
        'description' => 'Separator between agenda item levels',
    ],

    'days_1' => 'Monday',
    'days_2' => 'Tuesday',
    'days_3' => 'Wednesday',
    'days_4' => 'Thursday',
    'days_5' => 'Friday',
    'days_6' => 'Saturday',
    'days_7' => 'Sunday',

    'months_1'  => 'January',
    'months_2'  => 'February',
    'months_3'  => 'March',
    'months_4'  => 'April',
    'months_5'  => 'May',
    'months_6'  => 'June',
    'months_7'  => 'July',
    'months_8'  => 'August',
    'months_9'  => 'September',
    'months_10' => 'October',
    'months_11' => 'November',
    'months_12' => 'December',
];
