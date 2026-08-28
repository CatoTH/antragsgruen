import { test, expect } from '../../fixtures';
import { loginAsConsultationAdmin, loginAsStdAdmin, logout } from '../../utils/auth';
import { expectBootboxDialog, acceptBootbox } from '../../utils/dom';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';
import { AdminMotionListPage } from '../../pages/AdminMotionListPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';
import { AdminUsersPage } from '../../pages/AdminUsersPage';
import { AdminTranslationPage } from '../../pages/AdminTranslationPage';
import { AdminAdminConsultationsPage } from '../../pages/AdminAdminConsultationsPage';

test.describe('Useradmin: AdminPermissions', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('admin permissions and role changes', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsConsultationAdmin(page);
        await new AdminIndexPage(page).open();

        await expect(page.locator('#consultationLink')).toBeVisible();
        await expect(page.locator('#translationLink')).toBeVisible();
        await expect(page.locator('#contentPages')).toBeVisible();
        await expect(page.locator('.motionType1')).toBeVisible();
        await expect(page.locator('.siteUsers')).toBeVisible();
        await expect(page.locator('.siteConsultationsLink')).toHaveCount(0);
        await expect(page.locator('.siteUserList')).toHaveCount(0);
        await expect(page.locator('.siteConfigLink')).toHaveCount(0);

        const consultationPage = new AdminConsultationPage(page);
        await consultationPage.open({ subdomain: 'stdparteitag', consultationPath: 'std-parteitag' });
        await expect(page.locator('#consultationSettingsForm')).toBeVisible();

        const motionListPage = new AdminMotionListPage(page);
        await motionListPage.open({ subdomain: 'stdparteitag', consultationPath: 'std-parteitag' });
        await expect(page.locator('.motionListForm')).toBeVisible();

        const translationPage = new AdminTranslationPage(page);
        await translationPage.open({ subdomain: 'stdparteitag', consultationPath: 'std-parteitag' });
        await expect(page.locator('#wordingBaseForm')).toBeVisible();

        const motionTypePage = new AdminMotionTypePage(page);
        await motionTypePage.open({
            subdomain: 'stdparteitag',
            consultationPath: 'std-parteitag',
            motionTypeId: 1,
        });
        await expect(page.locator('.adminTypeForm')).toBeVisible();

        const usersPage = new AdminUsersPage(page);
        await usersPage.open({ subdomain: 'stdparteitag', consultationPath: 'std-parteitag' });
        await expect(page.locator('.userAdminList')).toBeVisible();

        const adminConsultationsPage = new AdminAdminConsultationsPage(page);
        await adminConsultationsPage.open({
            subdomain: 'stdparteitag',
            consultationPath: 'std-parteitag',
        });
        await expect(page.locator('body')).toContainText('Kein Zugriff auf diese Seite');
        await expect(page.locator('.consultationEditForm')).toHaveCount(0);

        await new ConsultationHomePage(page).open();
        await logout(page);

        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();
        await page.locator('.user7').waitFor({ timeout: 10_000 });

        await expect(page.locator('.editUserModal.in')).toHaveCount(0);
        await page.locator('.user7 .btnEdit').click();
        await page.locator('.editUserModal.in').waitFor({ timeout: 10_000 });
        await expect(page.locator('.editUserModal')).toBeVisible();
        await page.locator('.editUserModal .userGroup1 input').check();
        await page.locator('.editUserModal .btnSave').click();
        await expect(page.locator('.user7')).toContainText('Seiten-Admin');

        await logout(page);

        await new ConsultationHomePage(page).open();
        await loginAsConsultationAdmin(page);
        await new AdminIndexPage(page).open();

        await expect(page.locator('#consultationLink')).toBeVisible();
        await expect(page.locator('#translationLink')).toBeVisible();
        await expect(page.locator('#contentPages')).toBeVisible();
        await expect(page.locator('.motionType1')).toBeVisible();
        await expect(page.locator('.siteUsers')).toBeVisible();
        await expect(page.locator('.siteConsultationsLink')).toBeVisible();

        await usersPage.open({ subdomain: 'stdparteitag', consultationPath: 'std-parteitag' });
        await expect(page.locator('body')).not.toContainText('Kein Zugriff auf diese Seite');
        await expect(page.locator('.userAdminList')).toBeVisible();

        await adminConsultationsPage.open({
            subdomain: 'stdparteitag',
            consultationPath: 'std-parteitag',
        });
        await expect(page.locator('body')).not.toContainText('Kein Zugriff auf diese Seite');
        await expect(page.locator('.consultationEditForm')).toBeVisible();

        await new ConsultationHomePage(page).open();
        await logout(page);

        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();
        await page.locator('.user7').waitFor({ timeout: 10_000 });
        await page.locator('.user7 .btnEdit').click();
        await page.locator('.editUserModal.in').waitFor({ timeout: 10_000 });
        await expect(page.locator('.editUserModal')).toBeVisible();
        await page.locator('.editUserModal .userGroup3 input').click();
        await page.locator('.editUserModal .userGroup1 input').click();
        await page.locator('.editUserModal .userGroup2 input').click();
        await page.locator('.editUserModal .btnSave').click();
        await expect(page.locator('.user7')).toContainText('Antragskommission');

        await logout(page);

        await new ConsultationHomePage(page).open();
        await loginAsConsultationAdmin(page);

        await expect(page.locator('#motionListLink')).toBeVisible();
        await expect(page.locator('#adminLink')).toHaveCount(0);
        await motionListPage.open({ subdomain: 'stdparteitag', consultationPath: 'std-parteitag' });
        await expect(page.locator('.actionCol')).toHaveCount(0);
        await expect(page.locator('.proposalCol')).toBeVisible();

        await new ConsultationHomePage(page).open();
        await logout(page);

        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();
        await expect(page.locator('.userAdminList')).toContainText('consultationadmin@example.org');
        await page.locator('.userAdminList .user7 .btnRemove').click();
        await expectBootboxDialog(page, /Single-Consultation Admin wirklich aus der Liste entfernen/);
        await acceptBootbox(page);
        await expect(page.locator('.userAdminList')).not.toContainText('consultationadmin@example.org');

        await logout(page);
        await new ConsultationHomePage(page).open();
        await loginAsConsultationAdmin(page);
        await new AdminIndexPage(page).open();
        await expect(page.locator('body')).toContainText('Kein Zugriff auf diese Seite');
        await expect(page.locator('.adminIndex')).toHaveCount(0);

        await new ConsultationHomePage(page).open();
        await logout(page);

        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();

        await page.locator('.addUsersOpener.email').click();
        await page.locator('#emailAddresses').fill('consultationadmin@example.org');
        await page.locator('#names').fill('ignored');
        await page.locator('.addUsersByLogin.multiuser [name="addUsers"]').click();

        await page.locator('.user7').waitFor({ timeout: 10_000 });
        await page.locator('.user7 .btnEdit').click();
        await page.locator('.editUserModal.in').waitFor({ timeout: 10_000 });
        await expect(page.locator('.editUserModal')).toBeVisible();
        await page.locator('.editUserModal .userGroup2 input').check();
        await page.locator('.editUserModal .btnSave').click();

        await expect(page.locator('.user7')).toContainText('Veranstaltungs-Admin');

        await logout(page);
        await new ConsultationHomePage(page).open();
        await loginAsConsultationAdmin(page);
        await new AdminIndexPage(page).open();
        await expect(page.locator('#consultationLink')).toBeVisible();
    });
});