import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { disableCurrentlyDebated } from '../../utils/navigation';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { dispatchClick } from '../../utils/dom';

test.describe('Speech: Point of Order', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enable speech lists with Point of Orders', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await expect(page.locator('.currentSpeechInline').filter({ visible: true })).toHaveCount(0);

        await loginAsStdAdmin(page);
        // The fixture has the "Currently debated" module on, which would take the place of the voting widget
        await disableCurrentlyDebated(page);
        await new AdminIndexPage(page).open();
        await page.locator('.speechAdminLink').click();

        await expect(page.locator('.settingsActive .inactive').first()).toBeVisible();
        await dispatchClick(page, '.settingsActive button');
        await expect(page.locator('.settingsActive .inactive').filter({ visible: true })).toHaveCount(0);

        await page.locator('.settingOpen input').first().check();

        await page.locator('.settingOpenPoo input').first().check();

        await test.step('create a regular speaking list entry and a point of order', async () => {
            await expect(page.locator('.subqueues .empty').first()).toBeVisible();
            await dispatchClick(page, '.subqueueAdder .adderOpener');
            await page.locator('#subqueueAdderName-1').first().fill('Regular speech');
            await dispatchClick(page, '.subqueueAdder form button');
            await expect(page.locator('.subqueues .empty').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.slotPlaceholder')).toContainText('Regular speech');

            await new ConsultationHomePage(page).open();
            await logout(page);
            await expect(page.locator('.applyOpener').first()).toBeVisible();
            await dispatchClick(page, '.applyOpenerPoo');
            await page.locator('#speechRegisterName-1').first().fill('My Point');
            await dispatchClick(page, '.waitingSingle form button');
            await expect(page.locator('.waitingSingle')).toContainText('Warteliste: 2');
            await expect(page.locator('.waitingSingle')).toContainText('Regular speech');
            await expect(page.locator('.waitingSingle')).toContainText('My point');
            await expect(page.locator('.waitingSingle .label')).toContainText('GO-Antrag');

            await loginAsStdAdmin(page);
            await new AdminIndexPage(page).open();
        });

        await test.step('see the point of order up next and delete it again as admin', async () => {
            await page.locator('.speechAdminLink').click();
            await expect(page.locator('.slotPlaceholder')).toContainText('My point');
            await expect(page.locator('.slotPlaceholder .label')).toContainText('GO-Antrag');
            await expect(page.locator('.subqueueItems .subqueueItem:nth-child(2)')).toContainText('GO-Antrag');
            await expect(page.locator('.subqueueItems .subqueueItem:nth-child(4)')).toContainText('Regular speech');

            await dispatchClick(page, '.subqueueItems .subqueueItem:nth-child(2) .operationDelete');
            await expect(page.locator('.waitingSingle').getByText('GO-Antrag').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.slotPlaceholder')).toContainText('Regular speech');
        });
    });
});
