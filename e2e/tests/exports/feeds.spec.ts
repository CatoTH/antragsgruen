import { test, expect } from '../../fixtures';
import { expectFeedContains } from '../../utils/feeds';


test.describe('Exports: feeds', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('motion feed', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await test.step('test the motion feed', async () => {
            await page.locator('#sidebar .feeds a').click();

            await expectFeedContains(page, '.feedMotions', ['O’zapft is!', 'Test']);
        });
    });

    test('amendment feed', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await test.step('test the amendment feed', async () => {
            await page.locator('#sidebar .feeds a').click();

            await expectFeedContains(page, '.feedAmendments', ['Tester', 'Ä1']);
        });
    });

    test('overall feed', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await test.step('test the overall feed', async () => {
            await page.locator('#sidebar .feeds a').click();

            await expectFeedContains(page, '.feedAll', [
                'O’zapft is!',
                'Test',
                'Tester',
                'Ä1',
                'Oamoi a Maß',
                'Auf gehds beim Schichtl pfiad',
            ]);
        });
    });
});
