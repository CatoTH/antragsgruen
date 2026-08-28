import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';

test.describe('Speech: Point of Order', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enable speech lists with Point of Orders', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await expect(page.locator('.currentSpeechInline')).toHaveCount(0);

        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        await page.locator('.speechAdminLink').click();

        await expect(page.locator('.settingsActive .inactive')).toBeVisible();
        await page.locator('.settingsActive button').click();
        await expect(page.locator('.settingsActive .inactive')).toHaveCount(0);

        await page.locator('.settingOpen input').check();

        await page.locator('.settingOpenPoo input').check();

        await expect(page.locator('.subqueues .empty')).toBeVisible();
        await page.locator('.subqueueAdder .adderOpener').click();
        await page.locator('#subqueueAdderName-1').fill('Regular speech');
        await page.locator('.subqueueAdder form button').click();
        await expect(page.locator('.subqueues .empty')).toHaveCount(0);
        await expect(page.locator('.slotPlaceholder')).toContainText('Regular speech');

        await new ConsultationHomePage(page).open();
        await logout(page);
        await expect(page.locator('.applyOpener')).toBeVisible();
        await page.locator('.applyOpenerPoo').click();
        await page.locator('#speechRegisterName-1').fill('My Point');
        await page.locator('.waitingSingle form button').click();
        await expect(page.locator('.waitingSingle')).toContainText('Warteliste: 2');
        await expect(page.locator('.waitingSingle')).toContainText('Regular speech');
        await expect(page.locator('.waitingSingle')).toContainText('My point');
        await expect(page.locator('.waitingSingle .label')).toContainText('GO-Antrag');

        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        await page.locator('.speechAdminLink').click();
        await expect(page.locator('.slotPlaceholder')).toContainText('My point');
        await expect(page.locator('.slotPlaceholder .label')).toContainText('GO-Antrag');
        await expect(page.locator('.subqueueItems .subqueueItem:nth-child(2)')).toContainText('GO-Antrag');
        await expect(page.locator('.subqueueItems .subqueueItem:nth-child(4)')).toContainText('Regular speech');

        await page.locator('.subqueueItems .subqueueItem:nth-child(2) .operationDelete').click();
        await expect(page.locator('.waitingSingle')).not.toContainText('GO-Antrag');
        await expect(page.locator('.slotPlaceholder')).toContainText('Regular speech');
    });
});
