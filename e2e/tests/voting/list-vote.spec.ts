import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { FIRST_FREE_VOTING_BLOCK_ID } from '../../utils/constants';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { VotingAdminPage } from '../../pages/VotingAdminPage';

const TEMPLATE_YES = '3';
const VOTING_ID = `#voting${FIRST_FREE_VOTING_BLOCK_ID}`;

test.describe('List voting with a vote limit', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('a multi-question voting limits the number of votes per user', async ({ page }) => {
        const votingAdmin = new VotingAdminPage(page);
        await votingAdmin.open();
        await loginAsStdAdmin(page);
        await votingAdmin.open();

        await expect(page.locator('form.creatingVoting')).toHaveCount(0);
        await page.locator('.createVotingOpener').click();
        await expect(page.locator('form.creatingVoting')).toBeVisible();

        await expect(page.locator('input[name=votingTypeNew]:checked')).toHaveValue('question');
        await page.locator('.creatingVoting .settingsTitle').fill('Pick your two favorite animals');
        await page.locator('.creatingVoting .settingsQuestion').fill('Dog');
        await expect(page.locator('.creatingVoting .majorityTypeSettings')).toBeVisible();
        await page.locator(`input[name=answersNew][value="${TEMPLATE_YES}"]`).click();
        await expect(page.locator('.creatingVoting .majorityTypeSettings')).toHaveCount(0);
        await page.locator('form.creatingVoting button[type=submit]').click();

        await expect(page.locator(VOTING_ID)).toBeVisible();
        await expect(page.locator(`${VOTING_ID} h2`)).toContainText(
            'Pick your two favorite animals',
        );
        await expect(page.locator(`${VOTING_ID} .majorityType`)).toHaveCount(0);
        await expect(
            page.locator(`${VOTING_ID} .voting_question_1 .titleLink`),
        ).toContainText('Dog');

        for (const animal of ['Cat', 'Cow', 'Elephant']) {
            await expect(
                page.locator(`#voting_question_${FIRST_FREE_VOTING_BLOCK_ID}`),
            ).toHaveCount(0);
            await page.locator(`${VOTING_ID} .addingItemsForm .addQuestions`).click();
            await page
                .locator(`#voting_question_${FIRST_FREE_VOTING_BLOCK_ID}`)
                .fill(animal);
            await page.locator(`${VOTING_ID} .addingQuestions button[type=submit]`).click();
        }

        await expect(page.locator(`${VOTING_ID} .voting_question_2 .titleLink`)).toContainText(
            'Cat',
        );
        await expect(page.locator(`${VOTING_ID} .voting_question_3 .titleLink`)).toContainText(
            'Cow',
        );
        await expect(page.locator(`${VOTING_ID} .voting_question_4 .titleLink`)).toContainText(
            'Elephant',
        );

        await page.locator(`${VOTING_ID} .settingsToggleGroup .btn`).click();
        await expect(
            page.locator(`${VOTING_ID} .votesMaxVotes input:checked`),
        ).toHaveValue('0');
        await expect(page.locator(`${VOTING_ID} .votesMaxVotesAll`)).toHaveCount(0);
        await page.locator(`${VOTING_ID} .votesMaxVotes .maxVotesAll input`).click();
        await expect(page.locator(`${VOTING_ID} .votesMaxVotesAll`)).toBeVisible();

        await page.evaluate(() => {
            const w = window as any;
            w.votingAdminWidget.$refs['voting-admin-widget'][1].setMaxVotesRestrictionAll('2');
        });
        await page.locator(`${VOTING_ID} .votingSettings .btnSave`).click();
        await page.locator(`${VOTING_ID} .btnOpen`).click();

        const home = new ConsultationHomePage(page);
        await home.open();
        await expect(page.locator('.voting_question_4')).toContainText('Elephant');
        await expect(page.locator('.voting_question_1 .btnYes')).toBeVisible();
        await expect(page.locator('.voting_question_1 .btnNo')).toHaveCount(0);

        await expect(page.locator('.currentVoting')).toContainText(
            'Du hast noch 2 Stimmen zu vergeben.',
        );
        await page.locator('.voting_question_3 .btnYes').click();

        await expect(page.locator('.currentVoting')).toContainText(
            'Du hast noch 1 Stimme zu vergeben.',
        );
        await expect(page.locator('.voting_question_1 .btnYes')).toBeVisible();
        await page.locator('.voting_question_4 .btnYes').click();

        await expect(page.locator('.currentVoting')).toContainText(
            'Du hast alle Stimmen abgegeben.',
        );
        await expect(page.locator('.voting_question_1 .btnYes')).toHaveCount(0);
        await expect(page.locator('.voting_question_2 .btnYes')).toHaveCount(0);
        await expect(page.locator('.voting_question_3 .btnYes')).toHaveCount(0);
        await expect(page.locator('.voting_question_4 .btnYes')).toHaveCount(0);

        await page.locator('.votingsAdminLink').click();

        await expect(page.locator('.voting_question_1 .voteCount_yes')).toContainText('0');
        await expect(page.locator('.voting_question_2 .voteCount_yes')).toContainText('0');
        await expect(page.locator('.voting_question_3 .voteCount_yes')).toContainText('1');
        await expect(page.locator('.voting_question_4 .voteCount_yes')).toContainText('1');
        await expect(page.locator('.voting_question_4 .voteCount_no')).toHaveCount(0);
        await expect(page.locator('.voting_question_4 .voteCount_abstention')).toHaveCount(0);

        await page.locator(`${VOTING_ID} .btnClose`).click();
        await expect(page.locator('.voting_question_4 .result')).toHaveCount(0);
    });
});
