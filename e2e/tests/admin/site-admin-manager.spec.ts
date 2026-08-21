import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/BasePage';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { dispatchClick, expectBootboxDialog, acceptBootbox } from '../../utils/dom';

test.describe('Admin: SiteAdminManager', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('login as regular user and verify no admin link', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdUser(page);
        await expect(page.locator('#adminLink')).not.toContainText('Einstellungen');
        await logout(page);
    });

    test('add testuser as admin using the batch-creation mode', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('#userAdministrationLink').click();

        await expect(page.locator('.user2')).not.toBeVisible();
        await dispatchClick(page, '.addUsersOpener.email');
        await page.locator('#emailAddresses').fill('testuser@example.org');
        await page.locator('#names').fill('ignored');
        await page.locator('.addUsersByLogin.multiuser [name="addUsers"]').click();

        await expect(page.locator('.user2')).toBeVisible();
        await dispatchClick(page, '.user2 .btnEdit');
        await expect(page.locator('.editUserModal .userGroup4 input')).toBeChecked();
        await page.locator('.editUserModal .userGroup4 input').uncheck();
        await page.locator('.editUserModal .userGroup1 input').check();
        await dispatchClick(page, '.editUserModal .btnSave');
        await expect(page.locator('.user2')).toContainText('Seiten-Admin');
    });

    test('login in as testuser (now admin)', async ({ page }) => {
        await logout(page);
        await new ConsultationHomePage(page).open();
        await loginAsStdUser(page);
        await expect(page.locator('#adminLink')).toContainText('Einstellungen');
    });

    test('remove testadmin as admin', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('#userAdministrationLink').click();

        await expect(page.locator('body')).toContainText('testadmin@example.org');
        await dispatchClick(page, '.userAdminList .user1 .btnRemove');
        await expectBootboxDialog(page, /Testadmin wirklich aus der Liste entfernen/);
        await acceptBootbox(page);
        await expect(page.locator('body')).not.toContainText('testadmin@example.org');
    });

    test('login in as testadmin (now no admin)', async ({ page }) => {
        await logout(page);
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await expect(page.locator('#adminLink')).not.toContainText('Einstellungen');
    });
});