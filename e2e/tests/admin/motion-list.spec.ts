import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin } from '../../utils/auth';
import { dispatchClick, expectBootboxDialog, acceptBootbox } from '../../utils/dom';

test.describe('Admin: MotionList', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('open the page, screen, undo and delete items', async ({ page }) => {
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).open();
        await page.locator('#motionListLink').click();

        await test.step('check if the page can be opened at all', async () => {
            await expect(page.locator('body')).toContainText('O’zapft is!');
            await expect(page.locator('body')).toContainText('Textformatierungen');
            await expect(page.locator('body')).toContainText('Ä2');

            await expect(page.locator('.amendment1').first()).toContainText('Tester');
            await expect(page.locator('.amendment2').first()).toContainText('Testuser');
            await expect(page.locator('.motion2')).toContainText('Testuser');

            await expect(page.locator('.adminMotionTable').getByText('Ent-Freischalten').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '.motion2 .actionCol .dropdown-toggle');
            await expect(page.locator('.adminMotionTable')).toContainText('Ent-Freischalten');
            await dispatchClick(page, '.motion2 .actionCol .dropdown-toggle');
            await expect(page.locator('.adminMotionTable').getByText('Ent-Freischalten').filter({ visible: true })).toHaveCount(0);
        });

        await test.step('test screening and undoing it', async () => {
            await expect(page.locator('body')).not.toContainText('ungeprüft', { useInnerText: true });
            await page.locator('.motion3 input.selectbox').first().check();
            await page.locator('.amendment1 input.selectbox').first().check();
            await page.locator('.motionListForm [name="unscreen"]').click();
            await expect(page.locator('.motion3')).toContainText('ungeprüft');
            await expect(page.locator('.amendment1').first()).toContainText('ungeprüft');
            await page.locator('.motion3 input.selectbox').first().check();
            await page.locator('.amendment1 input.selectbox').first().check();
            await page.locator('.motionListForm [name="screen"]').click();
            await expect(page.locator('body')).not.toContainText('ungeprüft', { useInnerText: true });
        });

        await test.step('test deleting motions and amendments', async () => {
            await expect(page.locator('body')).toContainText('O’zapft is!');
            await page.locator('.motion2 input.selectbox').first().check();
            await page.locator('.motionListForm [name="delete"]').click();
            await expectBootboxDialog(page, /Wirklich löschen/);
            await acceptBootbox(page);

            await expect(page.locator('body')).not.toContainText('O’zapft is!', { useInnerText: true });
            await expect(page.locator('body')).toContainText('Textformatierungen');
            await page.locator('.amendment2 input.selectbox').first().check();
            await page.locator('.motionListForm [name="delete"]').click();
            await expectBootboxDialog(page, /Wirklich löschen/);
            await acceptBootbox(page);

            await expect(page.locator('.amendment2').first()).not.toBeVisible();
            await expect(page.locator('body')).toContainText('Textformatierungen');
        });
    });
});