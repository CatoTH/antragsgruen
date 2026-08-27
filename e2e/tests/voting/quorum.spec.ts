import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { disableCurrentlyDebated } from '../../utils/navigation';
import { FIRST_FREE_USERGROUP_ID, FIRST_FREE_VOTING_BLOCK_ID } from '../../utils/constants';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { VotingAdminPage } from '../../pages/VotingAdminPage';
import { AdminUsersPage } from '../../pages/AdminUsersPage';
import { dispatchClick } from '../../utils/dom';

const TEMPLATE_PRESENT = '2';
const POLICY_USER_GROUPS = '6';
const QUORUM_TYPE_HALF = '1';
const VOTING_ID = `#voting${FIRST_FREE_VOTING_BLOCK_ID}`;

test.describe('Voting quorum', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('a roll call with a quorum is only accepted once the quorum is reached', async ({
        page,
    }) => {
        const users = new AdminUsersPage(page);
        await users.open();
        await loginAsStdAdmin(page);
        // The fixture has the "Currently debated" module on, which would take the place of the voting widget
        await disableCurrentlyDebated(page);
        await users.open();

        await dispatchClick(page, '.btnGroupCreate');
        await page.locator('.addGroupName input').first().fill('Voting rights');
        await dispatchClick(page, '.addGroupForm .btnSave');
        await expect(page.locator(`.groupList .group${FIRST_FREE_USERGROUP_ID}`)).toContainText(
            'Voting rights',
        );

        await expect(page.locator('.user2').first()).not.toBeVisible();
        await dispatchClick(page, '.addUsersOpener.email');
        await page.locator('#emailAddresses').first().fill('testuser@example.org');
        await page.locator('#names').first().fill('Testuser');
        await page.locator('.addUsersByLogin.multiuser [name="addUsers"]').click();
        await expect(page.locator('.alert-success').first()).toBeVisible();

        for (const userId of ['1', '2', '7']) {
            await expect(page.locator(`.user${userId}`).first()).toBeVisible();
            await page.locator(`.user${userId} .btnEdit`).click();
            await expect(page.locator('.editUserModal').first()).toBeVisible();
            await page.locator(`.editUserModal .userGroup${FIRST_FREE_USERGROUP_ID}`).click();
            await dispatchClick(page, '.editUserModal .btnSave');
            await expect(page.locator(`.user${userId}`)).toContainText('Voting rights');
        }

        const votingAdmin = new VotingAdminPage(page);
        await votingAdmin.open();
        await dispatchClick(page, '.createVotingOpener');
        await expect(page.locator('form.creatingVoting').first()).toBeVisible();

        await expect(page.locator('input[name=votingTypeNew]:checked')).toHaveValue('question');
        await page.locator('.creatingVoting .settingsTitle').first().fill('Roll call');
        await page.locator('.creatingVoting .settingsQuestion').first().fill('Who is present?');

        await page.locator(`input[name=answersNew][value="${TEMPLATE_PRESENT}"]`).click();
        await expect(page.locator('.createVotingHolder .userGroupSelectList').filter({ visible: true })).toHaveCount(0);
        await page.locator('.createVotingHolder .policySelect').first().selectOption(POLICY_USER_GROUPS);
        await expect(page.locator('.createVotingHolder .userGroupSelectList').first()).toBeVisible();
        await page.evaluate((groupId) => {
            const el = document.querySelector(
                '.createVotingHolder select.userGroupSelectList',
            ) as any;
            el.selectize.addItem(groupId);
        }, FIRST_FREE_USERGROUP_ID);

        await expect(page.locator('input[name=resultsPublicNew]:checked')).toHaveValue('1');
        await page.locator('input[name=votesPublicNew][value="2"]').click();
        await page.locator('input[name=resultsPublicNew][value="1"]').click();
        await dispatchClick(page, 'form.creatingVoting button[type=submit]');

        await expect(page.locator(VOTING_ID).first()).toBeVisible();
        await expect(page.locator(`${VOTING_ID} h2`)).toContainText('Roll call');
        await page.locator(`${VOTING_ID} .settingsToggleGroup .dropdown-toggle`).click();
        await expect(
            page.locator(`${VOTING_ID} .votingSettings .selectize-control`),
        ).toBeVisible();
        await expect(page.locator(`${VOTING_ID} .quorumTypeSettings`).first()).toBeVisible();
        await page
            .locator(`${VOTING_ID} .quorumTypeSettings input[value="${QUORUM_TYPE_HALF}"]`)
            .click();
        await page.locator(`${VOTING_ID} .btnSave`).click();
        await expect(page.locator(`${VOTING_ID} .quorumType`)).toContainText(
            'Einfache Mehrheit (2 von 3 Berechtigten)',
        );

        await page.locator(`${VOTING_ID} .btnOpen`).click();
        await expect(page.locator(`${VOTING_ID} .quorumCounter`)).toContainText(
            'Quorum: 0 von 2 nötigen Stimmen',
        );

        const home = new ConsultationHomePage(page);
        await home.open();
        await expect(page.locator('.voting')).toContainText(
            'Alle Eingeloggte können die abgegebenen Stimmen einsehen.',
        );
        await dispatchClick(page, '.voting_question_1 .btnPresent');
        await expect(page.locator('.voting_question_1 .voted .present').first()).toBeVisible();

        await votingAdmin.open();
        await expect(page.locator(`${VOTING_ID} .quorumCounter`)).toContainText(
            'Quorum: 1 von 2 nötigen Stimmen',
        );
        await page.locator(`${VOTING_ID} .btnClose`).click();
        await expect(page.locator('.voting_question_1 .rejected')).toContainText(
            'Quorum verfehlt',
        );

        await page.locator(`${VOTING_ID} .btnReopen`).click();
        await home.open();
        await logout(page);
        await loginAsStdUser(page);
        await dispatchClick(page, '.voting_question_1 .btnPresent');
        await expect(page.locator('.voting_question_1 .voted .present').first()).toBeVisible();

        await logout(page);
        await loginAsStdAdmin(page);
        await votingAdmin.open();
        await expect(page.locator(`${VOTING_ID} .quorumCounter`)).toContainText(
            'Quorum: 2 von 2 nötigen Stimmen',
        );
        await page.locator(`${VOTING_ID} .btnClose`).click();
        await expect(page.locator('.voting_question_1 .accepted')).toContainText(
            'Quorum erreicht',
        );
        await expect(page.locator('.voting_question_1 .voteCount_present')).toContainText('2');

        await dispatchClick(page, '.voting_question_1 .btnShowVotes');
        await expect(page.locator('.voteResults')).toContainText('testadmin@example.org');
        await expect(page.locator('.voteResults')).toContainText('testuser@example.org');
    });
});
