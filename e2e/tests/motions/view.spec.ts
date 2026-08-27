import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';

test.describe('Motion view sidebar actions', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('a logged out user sees only the public actions', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionView(2);

        await test.step('see the motion as a regular / logged out user', async () => {
            await expect(page.locator('.sidebarActions .amendmentCreate').first()).toBeVisible();
            await expect(page.locator('.sidebarActions .download').first()).toBeVisible();
            await expect(page.locator('.sidebarActions .back').first()).toBeVisible();
            await expect(page.locator('.sidebarActions .edit').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.sidebarActions .mergeamendments').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.sidebarActions .adminEdit').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.sidebarActions .withdraw').filter({ visible: true })).toHaveCount(0);
        });
    });

    test('the initiating user additionally sees the withdraw action', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionView(2);
        await loginAsStdUser(page);
        await home.open();
        await home.gotoMotionView(2);

        await test.step('see the motion as the user who initiated it', async () => {
            await expect(page.locator('.sidebarActions .amendmentCreate').first()).toBeVisible();
            await expect(page.locator('.sidebarActions .download').first()).toBeVisible();
            await expect(page.locator('.sidebarActions .withdraw').first()).toBeVisible();
            await expect(page.locator('.sidebarActions .back').first()).toBeVisible();
            await expect(page.locator('.sidebarActions .edit').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.sidebarActions .mergeamendments').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.sidebarActions .adminEdit').filter({ visible: true })).toHaveCount(0);
        });
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

        await test.step('see the motion as an admin', async () => {
            await expect(page.locator('.sidebarActions .amendmentCreate').first()).toBeVisible();
            await expect(page.locator('.sidebarActions .download').first()).toBeVisible();
            await expect(page.locator('.sidebarActions .mergeamendments').first()).toBeVisible();
            await expect(page.locator('.sidebarActions .adminEdit').first()).toBeVisible();
            await expect(page.locator('.sidebarActions .back').first()).toBeVisible();
            await expect(page.locator('.sidebarActions .edit').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.sidebarActions .withdraw').filter({ visible: true })).toHaveCount(0);
        });
    });
});
