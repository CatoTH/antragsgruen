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
        await expect(page.locator('.sidebarActions .download').first()).toBeVisible();
        await expect(page.locator('.sidebarActions .edit').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.sidebarActions .adminEdit').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.sidebarActions .withdraw').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.sidebarActions .back').first()).toBeVisible();
        await expect(page.locator('.motionRow').first()).toBeVisible();

        await expect(page.locator('body')).toContainText('Von Zeile 1 bis 2:');
        await expect(page.locator('ins')).toContainText('Und noch eine neue Zeile');
        await expect(page.locator('body')).not.toContainText('Listenpunkt (kursiv)', { useInnerText: true });
    });

    test('toggle the full motion text view', async ({ page }) => {
        await new ConsultationHomePage(page).gotoAmendmentView(2);
        await test.step('test the full motion text view', async () => {
            await expect(page.locator('#section_2 .dropdown-menu .showFullText').filter({ visible: true })).toHaveCount(0);
        });

        await test.step('switch back to regular view', async () => {
            await dispatchClick(page, '#section_2 .dropdown-toggle');
            await expect(
                page.locator('#section_2 .dropdown-menu li.selected .showOnlyChanges'),
            ).toBeVisible();
            await expect(page.locator('#section_2 .dropdown-menu .showFullText').first()).toBeVisible();
            await dispatchClick(page, '#section_2 .dropdown-menu .showFullText');
            await expect(page.locator('#section_2 .dropdown-menu .showFullText').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('body')).not.toContainText('Von Zeile 1 bis 2:', { useInnerText: true });
            await expect(page.locator('ins')).toContainText('Und noch eine neue Zeile');
            await expect(page.locator('body')).toContainText('Listenpunkt (kursiv)');

            await dispatchClick(page, '#section_2 .dropdown-toggle');
            await expect(
                page.locator('#section_2 .dropdown-menu li.selected .showFullText'),
            ).toBeVisible();
            await dispatchClick(page, '#section_2 .dropdown-menu .showOnlyChanges');
            await expect(page.locator('body')).toContainText('Von Zeile 1 bis 2:');
            await expect(page.locator('ins')).toContainText('Und noch eine neue Zeile');
            await expect(page.locator('body')).not.toContainText('Listenpunkt (kursiv)', { useInnerText: true });
        });
    });

    test('see the amendment as the user who initiated it', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdUser(page);
        await new ConsultationHomePage(page).gotoAmendmentView(2);
        await test.step('check that I can edit the amendment now as the initiator', async () => {
            await expect(page.locator('.sidebarActions .download').first()).toBeVisible();
            await expect(page.locator('.sidebarActions .edit').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.sidebarActions .withdraw').first()).toBeVisible();
            await expect(page.locator('.sidebarActions .adminEdit').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.sidebarActions .back').first()).toBeVisible();
        });
    });

    test('see the amendment as an admin', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).gotoAmendmentView(2);
        await expect(page.locator('.sidebarActions .download').first()).toBeVisible();
        await expect(page.locator('.sidebarActions .edit').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.sidebarActions .withdraw').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.sidebarActions .adminEdit').first()).toBeVisible();
        await expect(page.locator('.sidebarActions .back').first()).toBeVisible();
    });

    test('allow users to edit their motions and verify it works', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('#consultationLink').click();
        await page.locator('#iniatorsMayEdit').first().check();
        await page.locator('#consultationSettingsForm [name="save"]').click();

        await logout(page);
        await loginAsStdUser(page);
        await new AmendmentPage(page).open({
            motionSlug: 3,
            amendmentId: 2,
        });
        await expect(page.locator('.sidebarActions .download').first()).toBeVisible();
        await expect(page.locator('.sidebarActions .edit').first()).toBeVisible();
        await expect(page.locator('.sidebarActions .withdraw').first()).toBeVisible();
        await expect(page.locator('.sidebarActions .adminEdit').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.sidebarActions .back').first()).toBeVisible();
    });
});