import { test, expect, Page } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { FIRST_FREE_USERGROUP_ID, FIRST_FREE_VOTING_BLOCK_ID } from '../../utils/constants';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { VotingAdminPage } from '../../pages/VotingAdminPage';
import { AdminUsersPage } from '../../pages/AdminUsersPage';

const TEMPLATE_PRESENT = '2';
const POLICY_USER_GROUPS = '6';
const VOTING_BASE_ID = `#voting${FIRST_FREE_VOTING_BLOCK_ID}`;

const EXPECTED_USER_GROUPS = [
    { id: 1, title: 'Seiten-Admin', member_count: 2 },
    { id: 2, title: 'Veranstaltungs-Admin', member_count: 1 },
    { id: 3, title: 'Antragskommission', member_count: 1 },
    { id: 4, title: 'Teilnehmer*in', member_count: 0 },
    { id: 39, title: 'Sachstände bearbeiten', member_count: 1 },
    { id: FIRST_FREE_USERGROUP_ID, title: 'Voting group', member_count: 1 },
];

async function createVotingGroup(page: Page): Promise<void> {
    const users = new AdminUsersPage(page);
    await users.open();
    await loginAsStdAdmin(page);
    await users.open();

    await page.locator('.btnGroupCreate').click();
    await expect(page.locator('.addGroupForm')).toBeVisible();
    await page.locator('.addGroupForm .addGroupName input').fill('Voting group');
    await page.locator('.addGroupForm .btnSave').click();
    await expect(page.locator(`.group${FIRST_FREE_USERGROUP_ID}`)).toContainText('Voting group');
}

async function createRestrictedVoting(page: Page): Promise<void> {
    const votingAdmin = new VotingAdminPage(page);
    await votingAdmin.open();

    await expect(page.locator('form.creatingVoting')).toHaveCount(0);
    await page.locator('.createVotingOpener').click();
    await expect(page.locator('form.creatingVoting')).toBeVisible();
    await expect(page.locator('input[name=votingTypeNew]:checked')).toHaveValue('question');

    await page.locator('.creatingVoting .settingsTitle').fill('Roll call');
    await page.locator('.creatingVoting .settingsQuestion').fill('Who is present?');
    await expect(page.locator('.majorityTypeSettings')).toBeVisible();
    await page.locator(`input[name=answersNew][value="${TEMPLATE_PRESENT}"]`).click();
    await expect(page.locator('.majorityTypeSettings')).toHaveCount(0);
    await expect(page.locator('input[name=resultsPublicNew]:checked')).toHaveValue('1');
    await page.locator('input[name=votesPublicNew][value="1"]').click();
    await page.locator('input[name=resultsPublicNew][value="1"]').click();

    await expect(
        page.locator('.createVotingHolder .votePolicy .userGroupSelect'),
    ).toHaveCount(0);
    await page
        .locator('.createVotingHolder .votePolicy .policySelect')
        .selectOption(POLICY_USER_GROUPS);
    await expect(page.locator('.createVotingHolder .votePolicy .userGroupSelect')).toBeVisible();

    const initialCount = await page.evaluate(() => {
        const el = document.querySelector(
            '.createVotingHolder .votePolicy .userGroupSelectList',
        ) as any;
        return el.selectize.items.length;
    });
    expect(initialCount).toBe(0);

    await page.evaluate(() => {
        const el = document.querySelector(
            '.createVotingHolder .votePolicy .userGroupSelectList',
        ) as any;
        el.selectize.addItem(1);
    });

    const afterCount = await page.evaluate(() => {
        const el = document.querySelector(
            '.createVotingHolder .votePolicy .userGroupSelectList',
        ) as any;
        return el.selectize.items.length;
    });
    expect(afterCount).toBe(1);

    await page.locator('form.creatingVoting button[type=submit]').click();

    await expect(
        page.locator(`${VOTING_BASE_ID} .votingSettingsSummary .votingPolicy`),
    ).toContainText('Seiten-Admin');
    await page.locator(`${VOTING_BASE_ID} .settingsToggleGroup button`).click();
    await expect(
        page.locator(`${VOTING_BASE_ID} .v-policy-select .selectize-control`),
    ).toBeVisible();

    const selected = await page.evaluate(() => {
        const w = window as any;
        return w.votingAdminWidget.$refs['voting-admin-widget'][1].$refs['policy-select']
            .userGroups;
    });
    expect(selected).toEqual([1]);

    await page.evaluate((groupId) => {
        const w = window as any;
        w.votingAdminWidget.$refs['voting-admin-widget'][1].$refs[
            'policy-select'
        ].setSelectedGroups([groupId]);
    }, FIRST_FREE_USERGROUP_ID);
    await page.locator(`${VOTING_BASE_ID} .btnSave`).click();
    await expect(
        page.locator(`${VOTING_BASE_ID} .votingSettingsSummary .votingPolicy`),
    ).toContainText('Voting group');
    await page.locator(`${VOTING_BASE_ID} .btnOpen`).click();
}

async function assignGroupWithWeight(page: Page): Promise<void> {
    const users = new AdminUsersPage(page);
    await users.open();

    await expect(page.locator('.user2')).toHaveCount(0);
    await page.locator('.addUsersOpener.email').click();
    await page.locator('#emailAddresses').fill('testuser@example.org');
    await page.locator('#names').fill('ignored');
    await page.locator('.addUsersByLogin.multiuser [name="addUsers"]').click();
    await expect(page.locator('.user2')).toBeVisible();

    await expect(page.locator('.user2 .selectize-control')).toHaveCount(0);
    await page.locator('.user2 .btnEdit').click();
    await expect(page.locator('.editUserModal')).toBeVisible();
    await page.locator('.editUserModal .userGroup4').click();
    await page.locator(`.editUserModal .userGroup${FIRST_FREE_USERGROUP_ID}`).click();
    await expect(page.locator('.editUserModal .inputVoteWeight')).toHaveValue('1');
    await page.locator('.editUserModal .inputVoteWeight').fill('7');
    await page.locator('.editUserModal .btnSave').click();

    await expect(page.locator('.user2')).not.toContainText('Veranstaltungs-Admin');
    await expect(page.locator('.user2')).not.toContainText('Teilnehmer*in');
    await expect(page.locator('.user2')).toContainText('Voting group');
}

test.describe('User group policy and weighted votes', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('a user outside the group cannot vote', async ({ page }) => {
        await createVotingGroup(page);
        await createRestrictedVoting(page);

        await logout(page);
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdUser(page);

        await expect(page.locator('h2')).toContainText('Roll call');
        await expect(page.locator('.voting_question_1')).toContainText('Who is present?');
        await expect(page.locator('.voting_question_1 .btnPresent')).toHaveCount(0);
    });

    test('a group member votes with their assigned weight', async ({ page }) => {
        await createVotingGroup(page);
        await createRestrictedVoting(page);

        await logout(page);
        await loginAsStdAdmin(page);
        await assignGroupWithWeight(page);

        await logout(page);
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdUser(page);

        await expect(page.locator('h2')).toContainText('Roll call');
        await expect(page.locator('.voting_question_1')).toContainText('Who is present?');
        await expect(page.locator('.currentVotings .votingWeight')).toContainText('7');
        await page.locator('.voting_question_1 .btnPresent').click();
        await expect(page.locator('.voting_question_1 span.present')).toBeVisible();

        await logout(page);
        await loginAsStdAdmin(page);
        const votingAdmin = new VotingAdminPage(page);
        await votingAdmin.open();
        await page.locator('.voting_question_1 .btnShowVotes').click();
        await expect(
            page.locator(`.voteListHolder${FIRST_FREE_USERGROUP_ID}`),
        ).toContainText('testuser@example.org (×7)');
        await expect(page.locator('.voting_question_1 .voteCount_present')).toContainText('7');
    });

    test('the open votings REST endpoint reflects the weighted vote', async ({ page }) => {
        await createVotingGroup(page);
        await createRestrictedVoting(page);

        await logout(page);
        await loginAsStdAdmin(page);
        await assignGroupWithWeight(page);

        await logout(page);
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdUser(page);
        await page.locator('.voting_question_1 .btnPresent').click();
        await expect(page.locator('.voting_question_1 span.present')).toBeVisible();

        const json = await page.evaluate(() =>
            fetch('/stdparteitag/rest/std-parteitag/votings/open?assignedToMotionId=').then((r) =>
                r.text(),
            ),
        );
        const parsed = JSON.parse(json);

        expect(parsed).toHaveLength(1);
        const block = parsed[0];
        expect(block.id).toBe(String(FIRST_FREE_VOTING_BLOCK_ID));
        expect(block.title).toBe('Roll call');
        expect(block.status).toBe(2);
        expect(block.votes_public).toBe(1);
        expect(block.results_public).toBe(1);
        expect(block.answers_template).toBe(2);
        expect(block.user_groups).toEqual(EXPECTED_USER_GROUPS);
        expect(block.votes_total).toBe(1);
        expect(block.votes_users).toBe(1);
        expect(block.vote_weight).toBe(7);
        expect(block.votes_remaining).toBeNull();
        expect(block.vote_policy).toEqual({
            id: 6,
            user_groups: [FIRST_FREE_USERGROUP_ID],
            description: 'Voting group',
        });

        expect(block.items).toHaveLength(1);
        expect(block.items[0].type).toBe('question');
        expect(block.items[0].title_with_prefix).toBe('Who is present?');
        expect(block.items[0].voted).toBe('present');
        expect(block.items[0].can_vote).toBe(false);
    });

    test('the admin votings REST endpoint reports the weighted results', async ({ page }) => {
        await createVotingGroup(page);
        await createRestrictedVoting(page);

        await logout(page);
        await loginAsStdAdmin(page);
        await assignGroupWithWeight(page);

        await logout(page);
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdUser(page);
        await page.locator('.voting_question_1 .btnPresent').click();
        await expect(page.locator('.voting_question_1 span.present')).toBeVisible();

        await logout(page);
        await loginAsStdAdmin(page);
        const votingAdmin = new VotingAdminPage(page);
        await votingAdmin.open();

        const json = await page.evaluate(() =>
            fetch('/stdparteitag/rest/std-parteitag/votings/admin').then((r) => r.text()),
        );
        const parsed = JSON.parse(json);

        expect(parsed).toHaveLength(2);

        const rollCall = parsed[0];
        expect(rollCall.id).toBe(String(FIRST_FREE_VOTING_BLOCK_ID));
        expect(rollCall.title).toBe('Roll call');
        expect(rollCall.status).toBe(2);
        expect(rollCall.user_groups).toEqual(EXPECTED_USER_GROUPS);
        expect(rollCall.max_votes_by_group).toBeNull();
        expect(rollCall.items[0].vote_results).toEqual([{ present: 7 }]);
        expect(rollCall.items[0].vote_eligibility).toEqual([
            {
                id: FIRST_FREE_USERGROUP_ID,
                title: 'Voting group',
                users: [{ user_id: 2, user_name: 'testuser@example.org', weight: 7 }],
            },
        ]);
        expect(rollCall.items[0].votes).toEqual([
            {
                vote: 'present',
                weight: 7,
                user_id: 2,
                user_name: 'testuser@example.org',
                user_groups: [FIRST_FREE_USERGROUP_ID],
            },
        ]);

        const amendmentVoting = parsed[1];
        expect(amendmentVoting.id).toBe('1');
        expect(amendmentVoting.title).toBe('Ä2 or Ä3');
        expect(amendmentVoting.status).toBe(0);
        expect(amendmentVoting.answers_template).toBe(0);
        expect(amendmentVoting.items).toHaveLength(3);
        expect(amendmentVoting.items.map((i: any) => i.prefix)).toEqual(['Ä2', 'Ä3', 'Ä6']);
        expect(amendmentVoting.votes_total).toBe(0);
        expect(amendmentVoting.vote_policy).toEqual({ id: 2, description: 'Eingeloggte' });
    });

    test('closing the voting shows the weighted result on the results page', async ({ page }) => {
        await createVotingGroup(page);
        await createRestrictedVoting(page);

        await logout(page);
        await loginAsStdAdmin(page);
        await assignGroupWithWeight(page);

        await logout(page);
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdUser(page);
        await page.locator('.voting_question_1 .btnPresent').click();
        await expect(page.locator('.voting_question_1 span.present')).toBeVisible();

        await logout(page);
        await loginAsStdAdmin(page);
        const votingAdmin = new VotingAdminPage(page);
        await votingAdmin.open();

        await page.locator(`.voting${FIRST_FREE_VOTING_BLOCK_ID} .btnClose`).click();
        await page.locator('.sidebarActions .results a').click();
        await expect(page.locator('.voting_question_1 .voteCount_present')).toContainText('7');

        const json = await page.evaluate(() =>
            document.querySelector('.currentVotingWidget')?.getAttribute('data-voting'),
        );
        const parsed = JSON.parse(json as string);

        expect(parsed).toHaveLength(1);
        const block = parsed[0];
        expect(block.status).toBe(3);
        expect(block.votes_public).toBe(1);
        expect(block.results_public).toBe(1);
        expect(block.user_groups).toEqual(EXPECTED_USER_GROUPS);
        expect(block.items[0].vote_results).toEqual([{ present: 7 }]);
        expect(block.items[0].vote_eligibility).toEqual([
            {
                id: FIRST_FREE_USERGROUP_ID,
                title: 'Voting group',
                users: [{ user_id: 2, user_name: 'testuser@example.org', weight: 7 }],
            },
        ]);
        expect(block.vote_policy).toEqual({
            id: 6,
            user_groups: [FIRST_FREE_USERGROUP_ID],
            description: 'Voting group',
        });
    });
});
