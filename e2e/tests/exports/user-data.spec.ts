import { test, expect } from '../../fixtures';
import { loginAsStdUser } from '../../utils/auth';

test.describe('Exports: user data', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('user data export contains supported motions and amendments', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdUser(page);

        await page.locator('#myAccountLink').click();
        await page.locator('.exportRow a').click();

        const content = await page.content();
        expect(content).toContain('supported_motions');
        expect(content).toContain('Testing_proposed_changes');
        expect(content).toContain('Und noch eine neue Zeile');
    });
});
