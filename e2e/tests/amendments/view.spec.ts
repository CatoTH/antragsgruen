import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { AmendmentPage } from '../../pages/AmendmentPage';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { dispatchClick } from '../../utils/dom';

test.describe('Amendments: View', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('see the amendment as a regular / logged out user', async ({ page }) => {
        await new ConsultationHomePage(page).gotoAmendmentView(2);
        await expect(page.locator('.sidebarActions .download')).toBeVisible();
        await expect(page.locator('.sidebarActions .edit')).not.toBeVisible();
        await expect(page.locator('.sidebarActions .adminEdit')).not.toBeVisible();
        await expect(page.locator('.sidebarActions .withdraw')).not.toBeVisible();
        await expect(page.locator('.sidebarActions .back')).toBeVisible();
        await expect(page.locator('.motionRow')).toBeVisible();

        await expect(page.locator('body')).toContainText('Von Zeile 1 bis 2:');
        await expect(page.locator('ins')).toContainText('Und noch eine neue Zeile');
        await expect(page.locator('body')).not.toContainText('Listenpunkt (kursiv)');
    });

    test('toggle the full motion text view', async ({ page }) => {
        await new ConsultationHomePage(page).gotoAmendmentView(2);
        await expect(page.locator('#section_2 .dropdown-menu .showFullText')).not.toBeVisible();
        await dispatchClick(page, '#section_2 .dropdown-toggle');
        await expect(
            page.locator('#section_2 .dropdown-menu li.selected .showOnlyChanges'),
        ).toBeVisible();
        await expect(page.locator('#section_2 .dropdown-menu .showFullText')).toBeVisible();
        await dispatchClick(page, '#section_2 .dropdown-menu .showFullText');
        await expect(page.locator('#section_2 .dropdown-menu .showFullText')).not.toBeVisible();
        await expect(page.locator('body')).not.toContainText('Von Zeile 1 bis 2:');
        await expect(page.locator('ins')).toContainText('Und noch eine neue Zeile');
        await expect(page.locator('body')).toContainText('Listenpunkt (kursiv)');

        await dispatchClick(page, '#section_2 .dropdown-toggle');
        await expect(
            page.locator('#section_2 .dropdown-menu li.selected .showFullText'),
        ).toBeVisible();
        await dispatchClick(page, '#section_2 .dropdown-menu .showOnlyChanges');
        await expect(page.locator('body')).toContainText('Von Zeile 1 bis 2:');
        await expect(page.locator('ins')).toContainText('Und noch eine neue Zeile');
        await expect(page.locator('body')).not.toContainText('Listenpunkt (kursiv)');
    });

    test('see the amendment as the user who initiated it', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdUser(page);
        await new ConsultationHomePage(page).gotoAmendmentView(2);
        await expect(page.locator('.sidebarActions .download')).toBeVisible();
        await expect(page.locator('.sidebarActions .edit')).not.toBeVisible();
        await expect(page.locator('.sidebarActions .withdraw')).toBeVisible();
        await expect(page.locator('.sidebarActions .adminEdit')).not.toBeVisible();
        await expect(page.locator('.sidebarActions .back')).toBeVisible();
    });

    test('see the amendment as an admin', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).gotoAmendmentView(2);
        await expect(page.locator('.sidebarActions .download')).toBeVisible();
        await expect(page.locator('.sidebarActions .edit')).not.toBeVisible();
        await expect(page.locator('.sidebarActions .withdraw')).not.toBeVisible();
        await expect(page.locator('.sidebarActions .adminEdit')).toBeVisible();
        await expect(page.locator('.sidebarActions .back')).toBeVisible();
    });

    test('allow users to edit their motions and verify it works', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('#consultationLink').click();
        await page.locator('#iniatorsMayEdit').check();
        await page.locator('#consultationSettingsForm [name="save"]').click();

        await logout(page);
        await loginAsStdUser(page);
        await new AmendmentPage(page).open({
            motionSlug: 3,
            amendmentId: 2,
        });
        await expect(page.locator('.sidebarActions .download')).toBeVisible();
        await expect(page.locator('.sidebarActions .edit')).toBeVisible();
        await expect(page.locator('.sidebarActions .withdraw')).toBeVisible();
        await expect(page.locator('.sidebarActions .adminEdit')).not.toBeVisible();
        await expect(page.locator('.sidebarActions .back')).toBeVisible();
    });
});