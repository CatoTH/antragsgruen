import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { dispatchClick, expectBootboxDialog, acceptBootbox } from '../../utils/dom';

test.describe('Admin: SiteAdminManager', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('login as regular user and verify no admin link', async ({ page }) => {
        await test.step('login as regular user and verify no admin link', async () => {
            await new ConsultationHomePage(page).open();
            await loginAsStdUser(page);
            await expect(page.locator('#adminLink').getByText('Einstellungen').filter({ visible: true })).toHaveCount(0);
            await logout(page);
        });

        await test.step('add testuser as admin using the batch-creation mode', async () => {
            await page.locator('#adminLink').click();
            await page.locator('#userAdministrationLink').click();

            await test.step('Logout again', async () => {
                await expect(page.locator('.user2').filter({ visible: true })).toHaveCount(0);
                await dispatchClick(page, '.addUsersOpener.email');
                await page.locator('#emailAddresses').first().fill('testuser@example.org');
                await page.locator('#names').first().fill('ignored');
                await page.locator('.addUsersByLogin.multiuser [name="addUsers"]').click();

                await expect(page.locator('.user2').first()).toBeVisible();
                await dispatchClick(page, '.user2 .btnEdit');
                await expect(page.locator('.editUserModal .userGroup4 input')).toBeChecked();
                await page.locator('.editUserModal .userGroup4 input').first().uncheck();
                await page.locator('.editUserModal .userGroup1 input').first().check();
                await dispatchClick(page, '.editUserModal .btnSave');
                await expect(page.locator('.user2')).toContainText('Seiten-Admin');
            });
        });

        await test.step('login in as testuser (now admin)', async () => {
            await logout(page);
            await new ConsultationHomePage(page).open();
            await loginAsStdUser(page);
            await expect(page.locator('#adminLink')).toContainText('Einstellungen');
        });

        await test.step('remove testadmin as admin', async () => {
            await page.locator('#adminLink').click();
            await page.locator('#userAdministrationLink').click();

            await expect(page.locator('body')).toContainText('testadmin@example.org');
            await dispatchClick(page, '.userAdminList .user1 .btnRemove');
            await expectBootboxDialog(page, /Testadmin wirklich aus der Liste entfernen/);
            await acceptBootbox(page);
            await expect(page.locator('body')).not.toContainText('testadmin@example.org', { useInnerText: true });
        });

        await test.step('login in as testadmin (now no admin)', async () => {
            await logout(page);
            await new ConsultationHomePage(page).open();
            await loginAsStdAdmin(page);
            await expect(page.locator('#adminLink').getByText('Einstellungen').filter({ visible: true })).toHaveCount(0);
        });
    });
});