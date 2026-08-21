import { test, expect } from '../../fixtures';
import {
    ConsultationHomePage,
} from '../../pages/BasePage';
import { MotionPage } from '../../pages/MotionPage';
import { loginAsStdAdmin } from '../../utils/auth';
import { FIRST_FREE_MOTION_ID, FIRST_FREE_MOTION_TITLE_PREFIX } from '../../utils/constants';
import { AdminMotionListPage } from '../../pages/AdminMotionListPage';

test.describe('Admin: MotionEditLineNumbering', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create a new motion and verify initial line numbering', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        const createPage = await home.gotoMotionCreatePage();
        await createPage.createMotion('random new motion', false);

        await new MotionPage(page).open({ motionSlug: FIRST_FREE_MOTION_ID });
        await expect(page.locator('h1')).toContainText('RANDOM NEW MOTION');

        const firstLineNo = await page.evaluate(() => {
            const w = window as any;
            return w.$('.motionData .text.freshParagraph .lineNumber').first().text();
        });
        expect(firstLineNo).toEqual('1');
    });

    test('enable global line numbering and verify', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('#consultationLink').click();

        await expect(page.locator('#lineNumberingGlobal')).not.toBeChecked();
        await page.locator('#lineNumberingGlobal').check();
        await page.locator('#consultationSettingsForm [name="save"]').click();
        await expect(page.locator('#lineNumberingGlobal')).toBeChecked();

        await new MotionPage(page).open({ motionSlug: FIRST_FREE_MOTION_ID });
        await expect(page.locator('h1')).toContainText('RANDOM NEW MOTION');

        const firstLineNo = await page.evaluate(() => {
            const w = window as any;
            return w.$('.motionData .text.freshParagraph .lineNumber').first().text();
        });
        expect(firstLineNo).toEqual('206');
    });

    test('set an invalid title prefix', async ({ page }) => {
        await loginAsStdAdmin(page);
        const motionList = new AdminMotionListPage(page);
        await page.locator('#motionListLink').click();
        await motionList.gotoMotionEdit(FIRST_FREE_MOTION_ID);

        await expect(page.locator('#motionTitlePrefix')).toHaveValue(FIRST_FREE_MOTION_TITLE_PREFIX);
        await page.locator('#motionTitlePrefix').fill('A2');
        await page.locator('#motionTitle').fill('Another Title');
        await page.locator('#motionUpdateForm [name="save"]').click();
        await expect(page.locator('body')).toContainText(
            'Das angegebene Antragskürzel wird bereits von einem anderen Antrag verwendet',
        );
        await expect(page.locator('#motionTitlePrefix')).toHaveValue(FIRST_FREE_MOTION_TITLE_PREFIX);
        await expect(page.locator('#motionTitle')).toHaveValue('Another Title');
    });

    test('set a correct title prefix', async ({ page }) => {
        await loginAsStdAdmin(page);
        const motionList = new AdminMotionListPage(page);
        await page.locator('#motionListLink').click();
        await motionList.gotoMotionEdit(FIRST_FREE_MOTION_ID);

        await page.locator('#motionTitlePrefix').fill('A1');
        await page.locator('#motionUpdateForm [name="save"]').click();
        await expect(page.locator('#motionTitlePrefix')).toHaveValue('A1');
    });

    test('check if the changes are reflected (motion 1A)', async ({ page }) => {
        await loginAsStdAdmin(page);
        await new MotionPage(page).open({ motionSlug: FIRST_FREE_MOTION_ID });
        await expect(page.locator('h1')).toContainText('ANOTHER TITLE');
        const firstLineNo = await page.evaluate(() => {
            const w = window as any;
            return w.$('.motionData .text.freshParagraph .lineNumber').first().text();
        });
        expect(firstLineNo).toEqual('1');

        await new MotionPage(page).open({ motionSlug: 2 });
        const firstLineNo2 = await page.evaluate(() => {
            const w = window as any;
            return w.$('.motionData .text.freshParagraph .lineNumber').first().text();
        });
        expect(firstLineNo2).toEqual('2');
    });

    test('disable global line numbering', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('#consultationLink').click();
        await expect(page.locator('#lineNumberingGlobal')).toBeChecked();
        await page.locator('#lineNumberingGlobal').uncheck();
        await page.locator('#consultationSettingsForm [name="save"]').click();
        await expect(page.locator('#lineNumberingGlobal')).not.toBeChecked();
    });

    test('verify line numbering is disabled again', async ({ page }) => {
        await new MotionPage(page).open({ motionSlug: FIRST_FREE_MOTION_ID });
        const firstLineNo = await page.evaluate(() => {
            const w = window as any;
            return w.$('.motionData .text.freshParagraph .lineNumber').first().text();
        });
        expect(firstLineNo).toEqual('1');

        await new MotionPage(page).open({ motionSlug: 2 });
        const firstLineNo2 = await page.evaluate(() => {
            const w = window as any;
            return w.$('.motionData .text.freshParagraph .lineNumber').first().text();
        });
        expect(firstLineNo2).toEqual('1');
    });
});