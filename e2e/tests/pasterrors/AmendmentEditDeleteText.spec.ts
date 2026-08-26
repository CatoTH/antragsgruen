import { test, expect } from '../../fixtures';
import { logout } from '../../utils/auth';
import { loginAndGotoMotionList } from '../../utils/navigation';

test.describe('AmendmentEditDeleteText', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('ensure the text does not get deleted', async ({ page }) => {
        await test.step("ensure the text doesn't get deleted", async () => {
            const motionList = await loginAndGotoMotionList(page);
            const amendment = await motionList.gotoAmendmentEdit(1);

            await amendment.saveForm();
            await page.locator('.sidebarActions .view').click();
            await expect(page.locator('del').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('ul.inserted').first()).toBeVisible();

            await logout(page);
        });

        // The Cept's second scenario (editing the title prefix on laenderrat-to) is commented out
        // there: "Broken, as original motion sections do not exist in laenderrat-to".
    });
});
