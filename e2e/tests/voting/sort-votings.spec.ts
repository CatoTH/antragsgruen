import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { FIRST_FREE_VOTING_BLOCK_ID } from '../../utils/constants';
import { VotingAdminPage } from '../../pages/VotingAdminPage';

const VOTING_ID_1 = 1;
const VOTING_ID_2 = FIRST_FREE_VOTING_BLOCK_ID;
const VOTING_ID_3 = FIRST_FREE_VOTING_BLOCK_ID + 1;

async function getSortedIds(page: import('@playwright/test').Page): Promise<number[]> {
    return page.evaluate(() => {
        const w = window as any;
        return w.votingAdminWidget.$refs['voting-sort-widget'].getSortedIds();
    });
}

test.describe('Sorting votings', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('votings can be reordered and the order persists', async ({ page }) => {
        const votingAdmin = new VotingAdminPage(page);
        await votingAdmin.open();
        await loginAsStdAdmin(page);
        await votingAdmin.open();

        await expect(page.locator('.votingOperations .sortVotings')).toHaveCount(0);
        await expect(page.locator('.votingSorting')).toHaveCount(0);

        await expect(page.locator('form.creatingVoting')).toHaveCount(0);
        await page.locator('.createVotingOpener').click();
        await page.locator('.creatingVoting .settingsTitle').fill('Vote on question 1');
        await page.locator('.creatingVoting .settingsQuestion').fill('Question 1?');
        await page.locator('form.creatingVoting button[type=submit]').click();
        await expect(page.locator(`.voting${FIRST_FREE_VOTING_BLOCK_ID}`)).toContainText(
            'Question 1?',
        );

        await expect(page.locator('form.creatingVoting')).toHaveCount(0);
        await page.locator('.createVotingOpener').click();
        await page.locator('.creatingVoting .settingsTitle').fill('Vote on question 2');
        await page.locator('.creatingVoting .settingsQuestion').fill('Question 2?');
        await page.locator('form.creatingVoting button[type=submit]').click();
        await expect(page.locator(`.voting${FIRST_FREE_VOTING_BLOCK_ID + 1}`)).toContainText(
            'Question 2?',
        );

        await expect(page.locator('.votingOperations .sortVotings')).toBeVisible();
        await page.locator('.votingOperations .sortVotings').click();
        await expect(page.locator('.votingSorting')).toBeVisible();

        const sortItems = page.locator('.votingSorting .list-group-item');
        await expect(sortItems).toContainText([
            'Ä2 or Ä3',
            'Vote on question 1',
            'Vote on question 2',
        ]);

        expect(await getSortedIds(page)).toEqual([VOTING_ID_3, VOTING_ID_2, VOTING_ID_1]);
        await page.evaluate(
            (order) => {
                const w = window as any;
                w.votingAdminWidget.$refs['voting-sort-widget'].setOrder(order);
            },
            [VOTING_ID_3, VOTING_ID_1, VOTING_ID_2],
        );
        expect(await getSortedIds(page)).toEqual([VOTING_ID_3, VOTING_ID_1, VOTING_ID_2]);
        await page.locator('.votingSorting .btnSave').click();

        await votingAdmin.open();
        await page.locator('.votingOperations .sortVotings').click();
        await expect(page.locator('.votingSorting')).toBeVisible();
        expect(await getSortedIds(page)).toEqual([VOTING_ID_3, VOTING_ID_1, VOTING_ID_2]);
    });
});
