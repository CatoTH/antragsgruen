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

        await page.evaluate(() => {
            const btn = document.querySelector('.user1 .btnEdit') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.editUserModal')).toBeVisible();
        await page.evaluate(() => {
            const btn = document.querySelector('.editUserModal .userGroup1') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await page.evaluate(() => {
            const btn = document.querySelector('.editUserModal .btnSave') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expectBootboxDialog(page, /Nur Seiten-Administrator\*innen selbst können ebendiese Gruppe verwalten/);
        await acceptBootbox(page);

        await page.evaluate(() => {
            const btn = document.querySelector('.user1 .btnRemove') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expectBootboxDialog(page, /Testadmin wirklich aus der Liste entfernen/);
        await acceptBootbox(page);
        await expectBootboxDialog(page, /Nur Seiten-Administrator\*innen selbst können ebendiese Gruppe verwalten/);
        await acceptBootbox(page);

        await page.evaluate(() => {
            const btn = document.querySelector('.user7 .btnEdit') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.editUserModal')).toBeVisible();
        await page.evaluate(() => {
            const btn = document.querySelector('.editUserModal .userGroup1') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await page.evaluate(() => {
            const btn = document.querySelector('.editUserModal .btnSave') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expectBootboxDialog(page, /Nur Seiten-Administrator\*innen selbst können ebendiese Gruppe verwalten/);
        await acceptBootbox(page);

        await page.evaluate(() => {
            const btn = document.querySelector('.user7 .btnEdit') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await page.evaluate(() => {
            const btn = document.querySelector('.editUserModal .userGroup1') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await page.evaluate(() => {
            const btn = document.querySelector('.editUserModal .userGroup2') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await page.evaluate(() => {
            const btn = document.querySelector('.editUserModal .btnSave') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expectBootboxDialog(page, /Es ist nicht möglich, sich selbst die Rechte zu dieser Seite zu entziehen/);
        await acceptBootbox(page);

        await page.evaluate(() => {
            const btn = document.querySelector('.user7 .btnRemove') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expectBootboxDialog(page, /Single-Consultation Admin wirklich aus der Liste entfernen/);
        await acceptBootbox(page);
        await expectBootboxDialog(page, /Es ist nicht möglich, sich selbst die Rechte zu dieser Seite zu entziehen/);
        await acceptBootbox(page);
    });
});