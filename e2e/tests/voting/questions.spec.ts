import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { disableCurrentlyDebated } from '../../utils/navigation';
import { FIRST_FREE_VOTING_BLOCK_ID } from '../../utils/constants';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { VotingAdminPage } from '../../pages/VotingAdminPage';
import { dispatchClick } from '../../utils/dom';

const TEMPLATE_YES_NO_ABSTENTION = '0';
const VOTING_ID = `#voting${FIRST_FREE_VOTING_BLOCK_ID}`;

test.describe('Voting on questions', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('questions can be added, removed, voted on and closed', async ({ page }) => {
        const votingAdmin = new VotingAdminPage(page);
        await votingAdmin.open();
        await loginAsStdAdmin(page);
        // The fixture has the "Currently debated" module on, which would take the place of the voting widget
        await disableCurrentlyDebated(page);
        await votingAdmin.open();

        await expect(page.locator('.quorumCounter').filter({ visible: true })).toHaveCount(0);

        await test.step('Create a voting with a question', async () => {
            await expect(page.locator('form.creatingVoting').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '.createVotingOpener');
            await expect(page.locator('form.creatingVoting').first()).toBeVisible();

            await expect(page.locator('input[name=votingTypeNew]:checked')).toHaveValue('question');
            await dispatchClick(page, 'input[name=votingTypeNew][value=motions]');
            await expect(page.locator('form.creatingVoting .specificQuestion').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, 'input[name=votingTypeNew][value=question]');
            await expect(page.locator('form.creatingVoting .specificQuestion').first()).toBeVisible();
        });

        await test.step('see that the voting was created successfully', async () => {
            await page.locator('.creatingVoting .settingsTitle').first().fill('Vote on these questions');
            await page.locator('.creatingVoting .settingsQuestion').first().fill('Is this cool?');
            await expect(page.locator('input[name=answersNew]:checked')).toHaveValue(
                TEMPLATE_YES_NO_ABSTENTION,
            );
            await expect(page.locator('input[name=resultsPublicNew]:checked')).toHaveValue('1');
            await expect(page.locator('input[name=votesPublicNew]:checked')).toHaveValue('0');
            await page.locator('input[name=votesPublicNew][value="1"]').click();
            await dispatchClick(page, 'form.creatingVoting button[type=submit]');

            await expect(page.locator(VOTING_ID).first()).toBeVisible();
            await expect(page.locator(`${VOTING_ID} h2`)).toContainText('Vote on these questions');
            await expect(page.locator(`${VOTING_ID} .majorityType`)).toContainText(
                'Einfache Mehrheit',
            );
            await expect(page.locator(`${VOTING_ID} .voting_question_1 .titleLink`)).toContainText(
                'Is this cool?',
            );
        });

        await test.step('Add another question (and remove one more)', async () => {
            await expect(
                page.locator(`#voting_question_${FIRST_FREE_VOTING_BLOCK_ID}`),
            ).not.toBeVisible();
            await page.locator(`${VOTING_ID} .addingItemsForm .addQuestions`).click();
            await page
                .locator(`#voting_question_${FIRST_FREE_VOTING_BLOCK_ID}`)
                .fill('Do you agree?');
            await page.locator(`${VOTING_ID} .addingQuestions button[type=submit]`).click();

            await page.locator(`${VOTING_ID} .addingItemsForm .addQuestions`).click();
            await page
                .locator(`#voting_question_${FIRST_FREE_VOTING_BLOCK_ID}`)
                .fill('One too much');
            await page.locator(`${VOTING_ID} .addingQuestions button[type=submit]`).click();

            await expect(page.locator(`${VOTING_ID} .voting_question_2 .titleLink`)).toContainText(
                'Do you agree?',
            );
            await expect(page.locator(`${VOTING_ID} .voting_question_3 .titleLink`)).toContainText(
                'One too much',
            );

            await page.locator(`${VOTING_ID} .voting_question_3 .removeBtn`).click();

            await expect(page.locator(`${VOTING_ID} .voting_question_2 .titleLink`)).toContainText(
                'Do you agree?',
            );
            await expect(
                page.locator(`${VOTING_ID} .voting_question_3 .titleLink`),
            ).not.toContainText('One too much', { useInnerText: true });
        });

        await test.step('Open the voting and participate', async () => {
            await page.locator(`${VOTING_ID} .btnOpen`).click();

            const home = new ConsultationHomePage(page);
            await home.open();

            await expect(page.locator('.voting_question_1')).toContainText('Is this cool');
            await expect(page.locator('.voting_question_2')).toContainText('Do you agree');
            await expect(page.locator('body')).not.toContainText('One too much', { useInnerText: true });

            await dispatchClick(page, '.voting_question_1 .btnYes');
            await dispatchClick(page, '.voting_question_2 .btnAbstention');
            await expect(page.locator('.voting_question_1 span.yes').first()).toBeVisible();
            await expect(page.locator('.voting_question_2 span.abstention').first()).toBeVisible();
        });

        await test.step('Finish the voting', async () => {
            await page.locator('.votingsAdminLink').click();

            await expect(page.locator('.voting_question_1 .voteCount_yes')).toContainText('1');
            await expect(page.locator('.voting_question_2 .voteCount_abstention')).toContainText('1');

            await page.locator(`${VOTING_ID} .btnClose`).click();

            await expect(page.locator('.voting_question_1 .result .accepted').first()).toBeVisible();
            await expect(page.locator('.voting_question_2 .result .rejected').first()).toBeVisible();
        });
    });
});
