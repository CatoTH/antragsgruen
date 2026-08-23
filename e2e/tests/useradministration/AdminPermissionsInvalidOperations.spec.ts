import { test, expect } from '../../fixtures';
import { loginAsConsultationAdmin } from '../../utils/auth';
import { expectBootboxDialog, acceptBootbox } from '../../utils/dom';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';

test.describe('Useradmin: AdminPermissionsInvalidOperations', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('invalid admin permission operations are blocked', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsConsultationAdmin(page);
        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();
        await page.locator('.user1').waitFor({ timeout: 10_000 });

        await page.locator('.user1 .btnEdit').click();
        await page.locator('.editUserModal.in').waitFor({ timeout: 10_000 });
        await expect(page.locator('.editUserModal')).toBeVisible();
        await page.locator('.editUserModal .userGroup1 input').click();
        await page.locator('.editUserModal .btnSave').click();
        await expectBootboxDialog(page, /Nur Seiten-Administrator\*innen selbst können ebendiese Gruppe verwalten/);
        await acceptBootbox(page);

        await page.locator('.user1 .btnRemove').click();
        await expectBootboxDialog(page, /Testadmin wirklich aus der Liste entfernen/);
        await acceptBootbox(page);
        await expectBootboxDialog(page, /Nur Seiten-Administrator\*innen selbst können ebendiese Gruppe verwalten/);
        await acceptBootbox(page);

        await page.locator('.user7 .btnEdit').click();
        await page.locator('.editUserModal.in').waitFor({ timeout: 10_000 });
        await expect(page.locator('.editUserModal')).toBeVisible();
        await page.locator('.editUserModal .userGroup1 input').click();
        await page.locator('.editUserModal .btnSave').click();
        await expectBootboxDialog(page, /Nur Seiten-Administrator\*innen selbst können ebendiese Gruppe verwalten/);
        await acceptBootbox(page);

        await page.locator('.user7 .btnEdit').click();
        await page.locator('.editUserModal.in').waitFor({ timeout: 10_000 });
        await page.locator('.editUserModal .userGroup1 input').click();
        await page.locator('.editUserModal .userGroup2 input').click();
        await page.locator('.editUserModal .btnSave').click();
        await expectBootboxDialog(page, /Es ist nicht möglich, sich selbst die Rechte zu dieser Seite zu entziehen/);
        await acceptBootbox(page);

        await page.locator('.user7 .btnRemove').click();
        await expectBootboxDialog(page, /Single-Consultation Admin wirklich aus der Liste entfernen/);
        await acceptBootbox(page);
        await expectBootboxDialog(page, /Es ist nicht möglich, sich selbst die Rechte zu dieser Seite zu entziehen/);
        await acceptBootbox(page);
    });
});