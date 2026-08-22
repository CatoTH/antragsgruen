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

        await expect(page.locator('#sidebar .amendmentCreate')).toBeVisible();
        await expect(page.locator('#sidebar .amendmentCreate .onlyAdmins')).toHaveCount(0);
    });

    test('a non-amendable motion stays amendable for admins only', async ({ page }) => {
        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });
        await loginAsStdAdmin(page);

        const motionList = new AdminMotionListPage(page);
        await motionList.open();
        await motionList.gotoMotionEdit(2);
        await expect(page.locator('#nonAmendable')).not.toBeChecked();
        await page.locator('#nonAmendable').check();
        await page.locator('#motionUpdateForm [name="save"]').click();

        await motion.open({ motionSlug: MOTION_SLUG });
        await expect(page.locator('#sidebar .amendmentCreate')).toBeVisible();
        await expect(page.locator('#sidebar .amendmentCreate .onlyAdmins')).toBeVisible();

        await logout(page);
        await motion.open({ motionSlug: MOTION_SLUG });
        await expect(page.locator('#sidebar .amendmentCreate')).toHaveCount(0);
        await expect(page.locator('#sidebar .amendmentCreate .onlyAdmins')).toHaveCount(0);
    });
});
