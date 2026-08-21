import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/BasePage';

test.describe('Motion view sidebar actions', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('a logged out user sees only the public actions', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionView(2);

        await expect(page.locator('.sidebarActions .amendmentCreate')).toBeVisible();
        await expect(page.locator('.sidebarActions .download')).toBeVisible();
        await expect(page.locator('.sidebarActions .back')).toBeVisible();
        await expect(page.locator('.sidebarActions .edit')).toHaveCount(0);
        await expect(page.locator('.sidebarActions .mergeamendments')).toHaveCount(0);
        await expect(page.locator('.sidebarActions .adminEdit')).toHaveCount(0);
        await expect(page.locator('.sidebarActions .withdraw')).toHaveCount(0);
    });

    test('the initiating user additionally sees the withdraw action', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionView(2);
        await loginAsStdUser(page);
        await home.open();
        await home.gotoMotionView(2);

        await expect(page.locator('.sidebarActions .amendmentCreate')).toBeVisible();
        await expect(page.locator('.sidebarActions .download')).toBeVisible();
        await expect(page.locator('.sidebarActions .withdraw')).toBeVisible();
        await expect(page.locator('.sidebarActions .back')).toBeVisible();
        await expect(page.locator('.sidebarActions .edit')).toHaveCount(0);
        await expect(page.locator('.sidebarActions .mergeamendments')).toHaveCount(0);
        await expect(page.locator('.sidebarActions .adminEdit')).toHaveCount(0);
    });

    test('an admin sees the admin and merge actions', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionView(2);
        await loginAsStdUser(page);
        await logout(page);
        await loginAsStdAdmin(page);
        await home.open();
        await home.gotoMotionView(2);

        await expect(page.locator('.sidebarActions .amendmentCreate')).toBeVisible();
        await expect(page.locator('.sidebarActions .download')).toBeVisible();
        await expect(page.locator('.sidebarActions .mergeamendments')).toBeVisible();
        await expect(page.locator('.sidebarActions .adminEdit')).toBeVisible();
        await expect(page.locator('.sidebarActions .back')).toBeVisible();
        await expect(page.locator('.sidebarActions .edit')).toHaveCount(0);
        await expect(page.locator('.sidebarActions .withdraw')).toHaveCount(0);
    });
});
