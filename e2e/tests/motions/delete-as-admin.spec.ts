import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { AdminIndexPage } from '../../pages/AdminIndexPage';

test.describe('Delete motion as admin', () => {
    test.skip(true, 'Not implemented yet in the original Codeception suite');

    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('an admin can delete a motion', async ({ page }) => {
        const admin = new AdminIndexPage(page);
        await admin.open();
        await loginAsStdAdmin(page);
        await expect(page.locator('h1').first()).toBeVisible();
    });
});
