import { test, expect } from '../../fixtures';

test.describe('Amendments: GotoPrefix', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('open an amendment using the prefix shortcut', async ({ page }) => {
        await page.goto('http://test.antragsgruen.test/stdparteitag/std-parteitag/A2/%C3%845');
        await expect(page.locator('body')).toContainText('und irgendw');
    });

    test('open a motion using a different subdomain prefix', async ({ page }) => {
        await page.goto(
            'http://test.antragsgruen.test/laenderrat-to/laenderrat-to/Z-01-224-1',
        );
        await expect(page.locator('h1')).toContainText('Z-01-224-1');
    });
});