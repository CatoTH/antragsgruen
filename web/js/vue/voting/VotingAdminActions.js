// @ts-check

// The operations the voting administration performs, without a widget around them.
//
// Two views host the same voting-admin-widget: the voting administration page, which shows every
// voting of the consultation, and the voting tab of the debate administration, which shows the one
// the debated item is voted on with. They differ in which votings they show and in nothing else, so
// what a button does lives here rather than in either of them.
//
// Every operation answers with the new state of *all* votings - the endpoints are the same ones the
// admin channel polls - so the caller is handed that list and decides what to show of it.

import { authorizedFetch } from "/js/modules/shared/ApiClient.js";

/**
 * @typedef {object} VotingAdminUrls
 * @property {string} voteSettings URL of the per-voting operations, with "VOTINGBLOCKID" as placeholder
 * @property {string|null} [voteCreate] only where votings can be created
 * @property {string|null} [sort] only where votings can be re-ordered
 */

/**
 * @param {VotingAdminUrls} urls
 * @param {function(any[]): void} onVotings called with the new state of all votings after every operation
 * @returns {object} the operations, named as the widget's events are
 */
export function createVotingAdminActions(urls, onVotings) {
    /**
     * @param {string} url
     * @param {object} postData
     * @returns {Promise<any|null>} the parsed response, or null if the request was refused
     */
    function post(url, postData) {
        return authorizedFetch(url, {
            method: "POST",
            headers: {"Content-Type": "application/json; charset=utf-8"},
            body: JSON.stringify(postData),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success !== undefined && !data.success) {
                    alert(data.message);
                    return null;
                }
                return data;
            })
            .catch(err => {
                console.error("Could not perform the voting operation", err);
                alert(err);
                return null;
            });
    }

    /**
     * One of the operations on a single voting. They all answer with the whole list.
     *
     * @returns {Promise<any[]|null>}
     */
    function performOperation(votingBlockId, additionalProps) {
        const url = urls.voteSettings.replace(/VOTINGBLOCKID/, votingBlockId);

        return post(url, additionalProps || {}).then(data => {
            if (data === null) {
                return null;
            }
            onVotings(data);
            return data;
        });
    }

    return {
        performOperation,

        setStatus(votingBlockId, newStatus) {
            return performOperation(votingBlockId, {
                op: 'update-status',
                status: newStatus,
            });
        },

        saveSettings(votingBlockId, title, answerTemplate, majorityType, quorumType, hasGeneralAbstention, votePolicy, maxVotesByGroup, resultsPublic, votesPublic, votingTime, assignedMotion, votesNames) {
            return performOperation(votingBlockId, {
                op: 'save-settings',
                title,
                answerTemplate,
                majorityType,
                quorumType,
                hasGeneralAbstention: (hasGeneralAbstention ? 1 : 0),
                votePolicy,
                maxVotesByGroup,
                resultsPublic,
                votesPublic,
                votingTime,
                assignedMotion,
                votesNames,
            });
        },

        removeItem(votingBlockId, itemType, itemId) {
            return performOperation(votingBlockId, {op: 'remove-item', itemType, itemId});
        },

        addIMotion(votingBlockId, itemDefinition) {
            return performOperation(votingBlockId, {op: 'add-imotion', itemDefinition});
        },

        addQuestion(votingBlockId, question) {
            return performOperation(votingBlockId, {op: 'add-question', question});
        },

        deleteVoting(votingBlockId) {
            return performOperation(votingBlockId, {op: 'delete-voting'});
        },

        setVotersToUserGroup(votingBlockId, userIds, newUserGroup) {
            return performOperation(votingBlockId, {op: 'set-voters-to-user-group', userIds, newUserGroup});
        },

        /**
         * Only offered where votings can be re-ordered, which is the administration page alone.
         * @returns {Promise<any[]|null>}
         */
        sortVotings(sortedIds) {
            if (!urls.sort) {
                throw new Error('This view cannot sort votings');
            }

            return post(urls.sort, {votingIds: sortedIds}).then(data => {
                if (data === null) {
                    return null;
                }
                onVotings(data);
                return data;
            });
        },

        /**
         * Only offered where votings can be created. Answers differently from the operations above:
         * the new list plus which of them was just created.
         *
         * @returns {Promise<{votings: any[], created_voting: number}|null>}
         */
        createVoting(postData) {
            if (!urls.voteCreate) {
                throw new Error('This view cannot create votings');
            }

            return post(urls.voteCreate, postData).then(data => {
                if (data === null) {
                    return null;
                }
                onVotings(data['votings']);
                return data;
            });
        },
    };
}
