export const STATUS = {
    DELETED: -2,

    // The motion has been withdrawn, either by the user or the admin.
    WITHDRAWN: -1,
    WITHDRAWN_INVISIBLE: -3,

    // The user has written the motion, but not yet confirmed to submit it.
    DRAFT: 1,

    // The user has submitted the motion, but it's not yet visible. It's up to the admin to screen it now.
    SUBMITTED_UNSCREENED: 2,
    SUBMITTED_UNSCREENED_CHECKED: 18,

    // The default state once the motion is visible
    SUBMITTED_SCREENED: 3,

    // This are stati motions and amendments get as their final state.
    // "Processed" is mostly used for amendments after merging amendments into th motion,
    // if it's unclear if it was adopted or rejected.
    // For member petitions, "Processed" means the petition has been replied.
    ACCEPTED: 4,
    REJECTED: 5,
    MODIFIED_ACCEPTED: 6,
    PROCESSED: 17,

    // This is the reply to a motion / member petition and is to be shown within the parent motion view.
    INLINE_REPLY: 24,

    // The initiator is still collecting supporters to actually submit this motion.
    // It's visible only to those who know the link to it.
    COLLECTING_SUPPORTERS: 15,

    // Not yet visible, it's up to the admin to submit it
    DRAFT_ADMIN: 16,

    // Saved drafts while merging amendments into an motion
    MERGING_DRAFT_PUBLIC: 19,
    MERGING_DRAFT_PRIVATE: 20,

    // The modified version of an amendment, as proposed by the admins.
    // This amendment is being referenced by proposalReference of the modified amendment.
    PROPOSED_MODIFIED_AMENDMENT: 21,

    // An amendment or motion has been referred to another institution.
    // The institution is documented in statusString, or, in case of a change proposal, in proposalComment
    REFERRED: 10,

    // An amendment becomes obsoleted by another amendment. That one is referred by an id
    // in statusString (a bit unelegantely), or, in case of a change proposal, in proposalComment
    OBSOLETED_BY: 22,

    // The exact status is specified in a free-text field, proposalComment if this status is used in proposalStatus
    CUSTOM_STRING: 23,

    // Purely informational statuses
    MODIFIED: 7,
    ADOPTED: 8,
    COMPLETED: 9,
    VOTE: 11,
    PAUSED: 12,
    MISSING_INFORMATION: 13,
    DISMISSED: 14,
};
