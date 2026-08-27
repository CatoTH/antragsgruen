import { test, expect } from '../../fixtures';
import { FIRST_FREE_VOTING_BLOCK_ID } from '../../utils/constants';
import {
    disableCurrentlyDebated,
    gotoConsultationHome,
    gotoStdAdminPage,
    loginAndGotoStdAdminPage,
} from '../../utils/navigation';
import { dispatchClick } from '../../utils/dom';

// app\models\votings\AnswerTemplates::TEMPLATE_YES
const TEMPLATE_YES = '3';
const VOTING_ID = `#voting${FIRST_FREE_VOTING_BLOCK_ID}`;

test.describe('List voting with a vote limit', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('a multi-question voting limits the number of votes per user', async ({ page }) => {
        await loginAndGotoStdAdminPage(page);
        // The fixture has the "Currently debated" module on, which would take the place of the voting widget
        await disableCurrentlyDebated(page);
        const admin = await gotoStdAdminPage(page);
        await admin.gotoVotingPage();

        await test.step('Create a voting with a question', async () => {
            await expect(page.locator('form.creatingVoting').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '.createVotingOpener');
            await expect(page.locator('form.creatingVoting').first()).toBeVisible();

            await expect(page.locator('input[name=votingTypeNew]:checked')).toHaveValue('question');
            await page
                .locator('.creatingVoting .settingsTitle')
                .fill('Pick your two favorite animals');
            await page.locator('.creatingVoting .settingsQuestion').first().fill('Dog');
            await expect(page.locator('.creatingVoting .majorityTypeSettings').first()).toBeVisible();
            await page.locator(`input[name=answersNew][value="${TEMPLATE_YES}"]`).click();
            await expect(page.locator('.creatingVoting .majorityTypeSettings').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, 'form.creatingVoting button[type=submit]');
        });

        await test.step('see that the voting was created successfully', async () => {
            await expect(page.locator(VOTING_ID).first()).toBeVisible();
            await expect(page.locator(`${VOTING_ID} h2`)).toContainText(
                'Pick your two favorite animals',
            );
            await expect(page.locator(`${VOTING_ID} .majorityType`).filter({ visible: true })).toHaveCount(0);
            await expect(page.locator(`${VOTING_ID} .voting_question_1 .titleLink`)).toContainText(
                'Dog',
            );
        });

        await test.step('Add more animals', async () => {
            for (const animal of ['Cat', 'Cow', 'Elephant']) {
                await expect(
                    page.locator(`#voting_question_${FIRST_FREE_VOTING_BLOCK_ID}`),
                ).not.toBeVisible();
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
        });

        await test.step('Set the vote limit', async () => {
            await page.locator(`${VOTING_ID} .settingsToggleGroup .btn`).click();
            await expect(page.locator(`${VOTING_ID} .votesMaxVotes input:checked`)).toHaveValue('0');
            await expect(page.locator(`${VOTING_ID} .votesMaxVotesAll`).filter({ visible: true })).toHaveCount(0);
            // Global limit. @TODO Also add a test for per-group limits
            await page.locator(`${VOTING_ID} .votesMaxVotes .maxVotesAll input`).click();
            await expect(page.locator(`${VOTING_ID} .votesMaxVotesAll`).first()).toBeVisible();

            await page.evaluate(() => {
                const w = window as any;
                w.votingAdminWidget.$refs['voting-admin-widget'][1].setMaxVotesRestrictionAll('2');
            });
            await page.locator(`${VOTING_ID} .votingSettings .btnSave`).click();
        });

        await test.step('Open the voting and participate', async () => {
            await page.locator(`${VOTING_ID} .btnOpen`).click();

            await gotoConsultationHome(page);
            await expect(page.locator('.voting_question_4')).toContainText('Elephant');
            await expect(page.locator('.voting_question_1 .btnYes').first()).toBeVisible();
            await expect(page.locator('.voting_question_1 .btnNo').filter({ visible: true })).toHaveCount(0);

            // The choice here is pretty obvios
            await expect(page.locator('.currentVoting')).toContainText(
                'Du hast noch 2 Stimmen zu vergeben.',
            );
            await dispatchClick(page, '.voting_question_3 .btnYes');

            await expect(page.locator('.currentVoting')).toContainText(
                'Du hast noch 1 Stimme zu vergeben.',
            );
            await expect(page.locator('.voting_question_1 .btnYes').first()).toBeVisible();
            await dispatchClick(page, '.voting_question_4 .btnYes');

            await expect(page.locator('.currentVoting')).toContainText(
                'Du hast alle Stimmen abgegeben.',
            );
            await expect(page.locator('.voting_question_1 .btnYes').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.voting_question_2 .btnYes').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.voting_question_3 .btnYes').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.voting_question_4 .btnYes').filter({ visible: true })).toHaveCount(0);
        });

        await test.step('Finish the voting', async () => {
            await page.locator('.votingsAdminLink').click();

            await expect(page.locator('.voting_question_1 .voteCount_yes')).toContainText('0');
            await expect(page.locator('.voting_question_2 .voteCount_yes')).toContainText('0');
            await expect(page.locator('.voting_question_3 .voteCount_yes')).toContainText('1');
            await expect(page.locator('.voting_question_4 .voteCount_yes')).toContainText('1');
            await expect(page.locator('.voting_question_4 .voteCount_no').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.voting_question_4 .voteCount_abstention').filter({ visible: true })).toHaveCount(0);

            await page.locator(`${VOTING_ID} .btnClose`).click();
            await expect(page.locator('.voting_question_4 .result').filter({ visible: true })).toHaveCount(0);
        });
    });
});
