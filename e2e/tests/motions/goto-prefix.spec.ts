import { test, expect } from '../../fixtures';

test.describe('Motion prefix shortcut', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('a motion can be opened via its prefix', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag/A2');
        await test.step('Open a motion using the prefix shortcut', async () => {
            await expect(page.locator('body')).toContainText('Wui helfgod Wiesn');
        });
    });

    test('a prefix shortcut works in another consultation', async ({ page }) => {
        await page.goto('/laenderrat-to/laenderrat-to/F-01');
        await expect(page.locator('h1')).toContainText('F-01');
    });
});
