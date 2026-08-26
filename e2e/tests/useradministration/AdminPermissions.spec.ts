import { test, expect } from '../../fixtures';
import { loginAsConsultationAdmin, loginAsStdAdmin, logout } from '../../utils/auth';
import { acceptBootbox, dispatchClick, expectBootboxDialog } from '../../utils/dom';
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

        await test.step('see the consultation-specific admin pages', async () => {
            await expect(page.locator('#consultationLink').first()).toBeVisible();
            await expect(page.locator('#translationLink').first()).toBeVisible();
            await expect(page.locator('#contentPages').first()).toBeVisible();
            await expect(page.locator('.motionType1').first()).toBeVisible();
            await expect(page.locator('.siteUsers').first()).toBeVisible();
            await expect(page.locator('.siteConsultationsLink').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.siteUserList').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.siteConfigLink').filter({ visible: true })).toHaveCount(0);

            const consultationPage = new AdminConsultationPage(page);
            await consultationPage.open({ subdomain: 'stdparteitag', consultationPath: 'std-parteitag' });
        });

        await expect(page.locator('#consultationSettingsForm').first()).toBeVisible();

        const motionListPage = new AdminMotionListPage(page);
        await motionListPage.open({ subdomain: 'stdparteitag', consultationPath: 'std-parteitag' });
        await expect(page.locator('.motionListForm').first()).toBeVisible();

        const translationPage = new AdminTranslationPage(page);
        await translationPage.open({ subdomain: 'stdparteitag', consultationPath: 'std-parteitag' });
        await expect(page.locator('#wordingBaseForm').first()).toBeVisible();

        const motionTypePage = new AdminMotionTypePage(page);
        await motionTypePage.open({
            subdomain: 'stdparteitag',
            consultationPath: 'std-parteitag',
            motionTypeId: 1,
        });
        await expect(page.locator('.adminTypeForm').first()).toBeVisible();

        const usersPage = new AdminUsersPage(page);
        await usersPage.open({ subdomain: 'stdparteitag', consultationPath: 'std-parteitag' });
        await expect(page.locator('.userAdminList').first()).toBeVisible();

        const adminConsultationsPage = new AdminAdminConsultationsPage(page);
        await adminConsultationsPage.open({
            subdomain: 'stdparteitag',
            consultationPath: 'std-parteitag',
        });
        await expect(page.locator('body')).toContainText('Kein Zugriff auf diese Seite');
        await expect(page.locator('.consultationEditForm').filter({ visible: true })).toHaveCount(0);

        await new ConsultationHomePage(page).open();
        await logout(page);

        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();
        await page.locator('.user7').waitFor({ timeout: 10_000 });

        await test.step('get permission by the more powerful admin', async () => {
            await expect(page.locator('.editUserModal.in').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '.user7 .btnEdit');
        });

        await test.step('Assign the site admin role to consultationadmin', async () => {
            await page.locator('.editUserModal.in').waitFor({ timeout: 10_000 });
            await expect(page.locator('.editUserModal').first()).toBeVisible();
            await page.locator('.editUserModal .userGroup1 input').first().check();
            await dispatchClick(page, '.editUserModal .btnSave');
            await expect(page.locator('.user7')).toContainText('Seiten-Admin');

            await logout(page);

            await new ConsultationHomePage(page).open();
            await loginAsConsultationAdmin(page);
            await new AdminIndexPage(page).open();
        });

        await test.step('see the rest of the admin pages as well', async () => {
            await expect(page.locator('#consultationLink').first()).toBeVisible();
            await expect(page.locator('#translationLink').first()).toBeVisible();
            await expect(page.locator('#contentPages').first()).toBeVisible();
            await expect(page.locator('.motionType1').first()).toBeVisible();
            await expect(page.locator('.siteUsers').first()).toBeVisible();
            await expect(page.locator('.siteConsultationsLink').first()).toBeVisible();

            await usersPage.open({ subdomain: 'stdparteitag', consultationPath: 'std-parteitag' });
            await expect(page.locator('body')).not.toContainText('Kein Zugriff auf diese Seite', { useInnerText: true });
            await expect(page.locator('.userAdminList').first()).toBeVisible();

            await adminConsultationsPage.open({
                subdomain: 'stdparteitag',
                consultationPath: 'std-parteitag',
            });
            await expect(page.locator('body')).not.toContainText('Kein Zugriff auf diese Seite', { useInnerText: true });
            await expect(page.locator('.consultationEditForm').first()).toBeVisible();

            await new ConsultationHomePage(page).open();
            await logout(page);

            await loginAsStdAdmin(page);
            await new AdminIndexPage(page).open();
            await page.locator('.siteUsers').click();
            await page.locator('.user7').waitFor({ timeout: 10_000 });
        });

        await test.step('be made to an proposed procedure admin', async () => {
            await dispatchClick(page, '.user7 .btnEdit');
            await page.locator('.editUserModal.in').waitFor({ timeout: 10_000 });
            await expect(page.locator('.editUserModal').first()).toBeVisible();
            await page.locator('.editUserModal .userGroup3 input').click();
            await page.locator('.editUserModal .userGroup1 input').click();
            await page.locator('.editUserModal .userGroup2 input').click();
            await dispatchClick(page, '.editUserModal .btnSave');
            await expect(page.locator('.user7')).toContainText('Antragskommission');

            await logout(page);

            await new ConsultationHomePage(page).open();
            await loginAsConsultationAdmin(page);

            await expect(page.locator('#motionListLink').first()).toBeVisible();
            await expect(page.locator('#adminLink').filter({ visible: true })).toHaveCount(0);
            await motionListPage.open({ subdomain: 'stdparteitag', consultationPath: 'std-parteitag' });
            await expect(page.locator('.actionCol').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.proposalCol').first()).toBeVisible();

            await new ConsultationHomePage(page).open();
            await logout(page);

            await loginAsStdAdmin(page);
            await new AdminIndexPage(page).open();
            await page.locator('.siteUsers').click();
        });

        await test.step('be resigned from being an admin', async () => {
            await expect(page.locator('.userAdminList')).toContainText('consultationadmin@example.org');
            await dispatchClick(page, '.userAdminList .user7 .btnRemove');
            await expectBootboxDialog(page, /Single-Consultation Admin wirklich aus der Liste entfernen/);
            await acceptBootbox(page);
            await expect(page.locator('.userAdminList').getByText('consultationadmin@example.org').filter({ visible: true })).toHaveCount(0);

            await logout(page);
            await new ConsultationHomePage(page).open();
            await loginAsConsultationAdmin(page);
            await new AdminIndexPage(page).open();
            await expect(page.locator('body')).toContainText('Kein Zugriff auf diese Seite');
            await expect(page.locator('.adminIndex').filter({ visible: true })).toHaveCount(0);

            await new ConsultationHomePage(page).open();
            await logout(page);

            await loginAsStdAdmin(page);
            await new AdminIndexPage(page).open();
            await page.locator('.siteUsers').click();
        });

        await test.step('be an admin like at the beginning', async () => {
            await dispatchClick(page, '.addUsersOpener.email');
            await page.locator('#emailAddresses').first().fill('consultationadmin@example.org');
            await page.locator('#names').first().fill('ignored');
            await page.locator('.addUsersByLogin.multiuser [name="addUsers"]').click();

            await page.locator('.user7').waitFor({ timeout: 10_000 });
            await dispatchClick(page, '.user7 .btnEdit');
            await page.locator('.editUserModal.in').waitFor({ timeout: 10_000 });
            await expect(page.locator('.editUserModal').first()).toBeVisible();
            await page.locator('.editUserModal .userGroup2 input').first().check();
            await dispatchClick(page, '.editUserModal .btnSave');

            await expect(page.locator('.user7')).toContainText('Veranstaltungs-Admin');

            await logout(page);
            await new ConsultationHomePage(page).open();
            await loginAsConsultationAdmin(page);
            await new AdminIndexPage(page).open();
            await expect(page.locator('#consultationLink').first()).toBeVisible();
        });

    });
});