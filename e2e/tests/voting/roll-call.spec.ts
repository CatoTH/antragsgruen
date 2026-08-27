import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { disableCurrentlyDebated } from '../../utils/navigation';
import { FIRST_FREE_VOTING_BLOCK_ID } from '../../utils/constants';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { VotingAdminPage } from '../../pages/VotingAdminPage';
import { VotingResultsPage } from '../../pages/VotingResultsPage';
import { dispatchClick } from '../../utils/dom';

const TEMPLATE_PRESENT = '2';
const VOTING_ID = `#voting${FIRST_FREE_VOTING_BLOCK_ID}`;

test.describe('Roll call voting', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('a roll call can be created, voted on and closed without publishing', async ({
        page,
    }) => {
        const votingAdmin = new VotingAdminPage(page);
        await votingAdmin.open();
        await loginAsStdAdmin(page);
        // The fixture has the "Currently debated" module on, which would take the place of the voting widget
        await disableCurrentlyDebated(page);
        await votingAdmin.open();

        await expect(page.locator('form.creatingVoting').filter({ visible: true })).toHaveCount(0);
        await dispatchClick(page, '.createVotingOpener');
        await expect(page.locator('form.creatingVoting').first()).toBeVisible();

        await expect(page.locator('input[name=votingTypeNew]:checked')).toHaveValue('question');
        await page.locator('.creatingVoting .settingsTitle').first().fill('Roll call');
        await page.locator('.creatingVoting .settingsQuestion').first().fill('Who is present?');
        await expect(page.locator('.majorityTypeSettings').first()).toBeVisible();

        await page.locator(`input[name=answersNew][value="${TEMPLATE_PRESENT}"]`).click();
        await expect(page.locator('.majorityTypeSettings').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('input[name=resultsPublicNew]:checked')).toHaveValue('1');
        await page.locator('input[name=votesPublicNew][value="2"]').click();
        await page.locator('input[name=resultsPublicNew][value="1"]').click();
        await dispatchClick(page, 'form.creatingVoting button[type=submit]');

        await expect(page.locator(VOTING_ID).first()).toBeVisible();
        await expect(page.locator(`${VOTING_ID} h2`)).toContainText('Roll call');
        await expect(page.locator(`${VOTING_ID} .majorityType`).filter({ visible: true })).toHaveCount(0);
        await expect(page.locator(`${VOTING_ID} .voting_question_1 .titleLink`)).toContainText(
            'Who is present?',
        );
        await page.locator(`${VOTING_ID} .btnOpen`).click();

        const home = new ConsultationHomePage(page);
        await home.open();
        await expect(page.locator('h2').filter({ hasText: 'Roll call' }).first()).toBeVisible();
        await expect(page.locator('.voting_question_1')).toContainText('Who is present?');
        await dispatchClick(page, '.voting_question_1 .btnPresent');
        await expect(page.locator('.voting_question_1 span.present').first()).toBeVisible();

        await page.locator('.votingsAdminLink').click();

        await expect(page.locator('.voting_question_1 .voteCount_present')).toContainText('1');
        await expect(page.locator('.voting_question_1 .result .accepted').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.voteResults').filter({ visible: true })).toHaveCount(0);
        await dispatchClick(page, '.voting_question_1 .btnShowVotes');
        await expect(page.locator('.voteResults')).toContainText('testadmin@example.org');

        await expect(page.locator(`${VOTING_ID} .btnPublish`).filter({ visible: true })).toHaveCount(0);
        await expect(page.locator(`${VOTING_ID} .btnCloseNopub`).filter({ visible: true })).toHaveCount(0);
        await page.locator(`${VOTING_ID} .btnClosePubOpener`).click();
        await expect(page.locator(`${VOTING_ID} .btnCloseNopub`).first()).toBeVisible();
        await page.locator(`${VOTING_ID} .btnCloseNopub`).click();
        await expect(page.locator(`${VOTING_ID} .btnPublish`).first()).toBeVisible();

        await home.open();
        await expect(page.locator('.voting_question_1').filter({ visible: true })).toHaveCount(0);

        const results = new VotingResultsPage(page);
        await results.open();
        await expect(page.locator('.votingsNoneIndicator').first()).toBeVisible();
        await expect(page.locator('.voting_question_1').filter({ visible: true })).toHaveCount(0);
    });

    test('published results are visible to logged in users', async ({ page }) => {
        const votingAdmin = new VotingAdminPage(page);
        await votingAdmin.open();
        await loginAsStdAdmin(page);
        await votingAdmin.open();

        await dispatchClick(page, '.createVotingOpener');
        await page.locator('.creatingVoting .settingsTitle').first().fill('Roll call');
        await page.locator('.creatingVoting .settingsQuestion').first().fill('Who is present?');
        await page.locator(`input[name=answersNew][value="${TEMPLATE_PRESENT}"]`).click();
        await page.locator('input[name=votesPublicNew][value="2"]').click();
        await page.locator('input[name=resultsPublicNew][value="1"]').click();
        await dispatchClick(page, 'form.creatingVoting button[type=submit]');
        await page.locator(`${VOTING_ID} .btnOpen`).click();

        const home = new ConsultationHomePage(page);
        await home.open();
        await dispatchClick(page, '.voting_question_1 .btnPresent');
        await expect(page.locator('.voting_question_1 span.present').first()).toBeVisible();

        await page.locator('.votingsAdminLink').click();
        await page.locator(`${VOTING_ID} .btnClosePubOpener`).click();
        await page.locator(`${VOTING_ID} .btnCloseNopub`).click();
        await expect(page.locator(`${VOTING_ID} .btnPublish`).first()).toBeVisible();

        await votingAdmin.open();
        await page.locator(`${VOTING_ID} .btnPublish`).click();
        await expect(page.locator(`${VOTING_ID} .btnPublish`).filter({ visible: true })).toHaveCount(0);

        await home.open();
        await expect(page.locator('.voting_question_1').filter({ visible: true })).toHaveCount(0);

        await logout(page);
        await page.locator('#votingResultsLink').click();
        await expect(page.locator('h1')).toContainText('Login');

        await loginAsStdUser(page);
        await page.locator('#votingResultsLink').click();
        await expect(page.locator('.votingsNoneIndicator').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.voting_question_1 .voteCount_present')).toContainText('1');
        await expect(page.locator('.voting_question_1 .result .accepted').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.regularVoteList').filter({ visible: true })).toHaveCount(0);
        await dispatchClick(page, '.voting_question_1 .btnShowVotes');
        await expect(page.locator('.regularVoteList')).toContainText('testadmin@example.org');
    });

    test('the published voting widget exposes the expected JSON payload', async ({ page }) => {
        const votingAdmin = new VotingAdminPage(page);
        await votingAdmin.open();
        await loginAsStdAdmin(page);
        await votingAdmin.open();

        await dispatchClick(page, '.createVotingOpener');
        await page.locator('.creatingVoting .settingsTitle').first().fill('Roll call');
        await page.locator('.creatingVoting .settingsQuestion').first().fill('Who is present?');
        await page.locator(`input[name=answersNew][value="${TEMPLATE_PRESENT}"]`).click();
        await page.locator('input[name=votesPublicNew][value="2"]').click();
        await page.locator('input[name=resultsPublicNew][value="1"]').click();
        await dispatchClick(page, 'form.creatingVoting button[type=submit]');
        await page.locator(`${VOTING_ID} .btnOpen`).click();

        const home = new ConsultationHomePage(page);
        await home.open();
        await dispatchClick(page, '.voting_question_1 .btnPresent');
        await expect(page.locator('.voting_question_1 span.present').first()).toBeVisible();

        await page.locator('.votingsAdminLink').click();
        await page.locator(`${VOTING_ID} .btnClosePubOpener`).click();
        await page.locator(`${VOTING_ID} .btnCloseNopub`).click();
        await votingAdmin.open();
        await page.locator(`${VOTING_ID} .btnPublish`).click();

        await logout(page);
        await loginAsStdUser(page);
        await page.locator('#votingResultsLink').click();
        await dispatchClick(page, '.voting_question_1 .btnShowVotes');

        const json = await page.evaluate(() =>
            document.querySelector('.currentVotingWidget')?.getAttribute('data-voting'),
        );
        expect(json).toBeTruthy();
        const parsed = JSON.parse(json as string);

        expect(parsed).toHaveLength(1);
        const block = parsed[0];
        expect(block.id).toBe(String(FIRST_FREE_VOTING_BLOCK_ID));
        expect(block.title).toBe('Roll call');
        expect(block.status).toBe(3);
        expect(block.votes_public).toBe(2);
        expect(block.votes_names).toBe(0);
        expect(block.results_public).toBe(1);
        expect(block.assigned_motion).toBeNull();
        expect(block.majority_type).toBe(1);
        expect(block.quorum_type).toBe(0);
        expect(block.answers_template).toBe(2);
        expect(block.answers).toEqual([
            { api_id: 'present', title: 'Anwesend', status_id: null },
        ]);
        expect(block.user_groups).toEqual([
            { id: 1, title: 'Seiten-Admin', member_count: 2 },
            { id: 2, title: 'Veranstaltungs-Admin', member_count: 1 },
            { id: 3, title: 'Antragskommission', member_count: 1 },
            { id: 4, title: 'Teilnehmer*in', member_count: 0 },
            { id: 39, title: 'Sachstände bearbeiten', member_count: 1 },
        ]);
        expect(block.abstentions_total).toBe(0);
        expect(block.has_general_abstention).toBe(false);
        expect(block.votes_total).toBe(1);
        expect(block.votes_users).toBe(1);
        expect(block.voting_time).toBeNull();
        expect(block.opened_ts).toBeNull();
        expect(block.vote_policy).toEqual({ id: 2, description: 'Eingeloggte' });

        expect(block.items).toHaveLength(1);
        const item = block.items[0];
        expect(item.type).toBe('question');
        expect(item.id).toBe(1);
        expect(item.prefix).toBe('');
        expect(item.title_with_prefix).toBe('Who is present?');
        expect(item.url_json).toBeNull();
        expect(item.url_html).toBeNull();
        expect(item.initiators_html).toBeNull();
        expect(item.procedure).toBeNull();
        expect(item.item_group_same_vote).toBeNull();
        expect(item.item_group_name).toBeNull();
        expect(item.voting_status).toBeNull();
        expect(item.vote_eligibility).toBeNull();
        expect(item.vote_results).toEqual([{ present: 1 }]);
        expect(item.votes).toEqual([
            {
                user_id: 1,
                user_groups: [1],
                user_name: 'testadmin@example.org',
                vote: 'present',
                weight: 1,
            },
        ]);
    });
});
