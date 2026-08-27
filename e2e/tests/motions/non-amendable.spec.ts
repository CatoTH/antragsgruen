import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { MotionPage } from '../../pages/MotionPage';
import { AdminMotionListPage } from '../../pages/AdminMotionListPage';

const MOTION_SLUG = '321-o-zapft-is';

test.describe('Non-amendable motions', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('motions are amendable by default', async ({ page }) => {
        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });

        await test.step('make sure it is amendable in the standard version', async () => {
            await expect(page.locator('#sidebar .amendmentCreate').first()).toBeVisible();
            await expect(page.locator('#sidebar .amendmentCreate .onlyAdmins').filter({ visible: true })).toHaveCount(0);
        });
    });

    test('a non-amendable motion stays amendable for admins only', async ({ page }) => {
        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });
        await loginAsStdAdmin(page);

        const motionList = new AdminMotionListPage(page);
        await motionList.open();
        await page.locator('.adminMotionTable .motion2 .titleCol a').click();
        await test.step('change the nonAmendable-setting', async () => {
            await expect(page.locator('#nonAmendable')).not.toBeChecked();
            await page.locator('#nonAmendable').first().check();
            await page.locator('#motionUpdateForm [name="save"]').click();

            await motion.open({ motionSlug: MOTION_SLUG });
        });

        await test.step('still be able to amend it as an admin', async () => {
            await expect(page.locator('#sidebar .amendmentCreate').first()).toBeVisible();
        });

        await test.step('not be able to amend it as a regular user', async () => {
            await expect(page.locator('#sidebar .amendmentCreate .onlyAdmins').first()).toBeVisible();

            await logout(page);
            await motion.open({ motionSlug: MOTION_SLUG });
            await expect(page.locator('#sidebar .amendmentCreate').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#sidebar .amendmentCreate .onlyAdmins').filter({ visible: true })).toHaveCount(0);
        });
    });
});
