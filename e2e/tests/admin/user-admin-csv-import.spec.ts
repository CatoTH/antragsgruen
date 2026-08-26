import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { dispatchClick } from '../../utils/dom';

test.describe('Admin: UserAdminCsvImport', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('CSV user import JS frontend logic', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('#userAdministrationLink').click();

        await test.step('Test CSV User Import JS Frontend Logic', async () => {
            await dispatchClick(page, '.addUsersOpener.csv');

            await expect(page.locator('#csvImportForm').first()).toBeVisible();
            await expect(page.locator('#csvSubmitBtn').first()).toBeVisible();

            await expect(page.locator('#csvProgressContainer:not(.hidden)').filter({ visible: true })).toHaveCount(0);

            await page.evaluate(() => {
                const input = document.querySelector(
                    'input[name="csvFile"]',
                ) as HTMLInputElement;
                input.removeAttribute('required');
            });

            await dispatchClick(page, '#csvSubmitBtn');

            await expect(page.locator('#csvProgressContainer:not(.hidden)').first()).toBeVisible();
        });
    });
});