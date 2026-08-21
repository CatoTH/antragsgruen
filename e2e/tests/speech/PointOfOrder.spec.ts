import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/BasePage';
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
        await page.evaluate(() => {
            const btn = document.querySelector('.settingsActive button') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.settingsActive .inactive')).toHaveCount(0);

        await page.evaluate(() => {
            const chkbox = document.querySelector('.settingOpen input') as HTMLInputElement;
            const evt = document.createEvent('HTMLEvents');
            chkbox.checked = true;
            evt.initEvent('change', false, true);
            chkbox.dispatchEvent(evt);
        });

        await page.evaluate(() => {
            const chkbox = document.querySelector('.settingOpenPoo input') as HTMLInputElement;
            const evt = document.createEvent('HTMLEvents');
            chkbox.checked = true;
            evt.initEvent('change', false, true);
            chkbox.dispatchEvent(evt);
        });

        await expect(page.locator('.subqueues .empty')).toBeVisible();
        await page.evaluate(() => {
            const btn = document.querySelector('.subqueueAdder .adderOpener') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await page.locator('#subqueueAdderName-1').fill('Regular speech');
        await page.evaluate(() => {
            const btn = document.querySelector('.subqueueAdder form button') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.subqueues .empty')).toHaveCount(0);
        await expect(page.locator('.slotPlaceholder')).toContainText('Regular speech');

        await new ConsultationHomePage(page).open();
        await logout(page);
        await expect(page.locator('.applyOpener')).toBeVisible();
        await page.evaluate(() => {
            const btn = document.querySelector('.applyOpenerPoo') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await page.locator('#speechRegisterName-1').fill('My Point');
        await page.evaluate(() => {
            const btn = document.querySelector('.waitingSingle form button') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
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

        await page.evaluate(() => {
            const btn = document.querySelector('.subqueueItems .subqueueItem:nth-child(2) .operationDelete') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.waitingSingle')).not.toContainText('GO-Antrag');
        await expect(page.locator('.slotPlaceholder')).toContainText('Regular speech');
    });
});