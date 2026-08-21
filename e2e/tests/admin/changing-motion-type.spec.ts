import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/BasePage';
import { loginAsStdAdmin } from '../../utils/auth';
import {
    FIRST_FREE_MOTION_SECTION,
    FIRST_FREE_MOTION_TYPE,
} from '../../utils/constants';
import { AdminMotionListPage } from '../../pages/AdminMotionListPage';

const COMPATIBLE = FIRST_FREE_MOTION_TYPE;
const INCOMPATIBLE = FIRST_FREE_MOTION_TYPE + 1;

test.describe('Admin: ChangingMotionType', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create two new motion types', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).open();
        await page.locator('#adminLink').click();

        await page.locator('.motionTypeCreate a').click();

        await page.locator('#typeTitleSingular').fill('Compatible motion');
        await page.locator('#typeTitlePlural').fill('Compatible motions');
        await page.locator('#typeCreateTitle').fill('Create');
        await page.locator('.preset1').check();
        await page.locator('.motionTypeCreateForm [name="create"]').click();

        await page
            .locator(`.section${FIRST_FREE_MOTION_SECTION} .sectionTitle input`)
            .fill('New title');
        await page
            .locator(`.section${FIRST_FREE_MOTION_SECTION + 1} .sectionTitle input`)
            .fill('New motion text');
        await page.locator('.adminTypeForm [name="save"]').click();

        await page.locator('#adminLink').click();
        await page.locator('.motionTypeCreate a').click();

        await page.locator('#typeTitleSingular').fill('Incompatible motion');
        await page.locator('#typeTitlePlural').fill('Incompatible motions');
        await page.locator('#typeCreateTitle').fill('Create');
        await page.locator('.presetApplication').check();
        await page.locator('.motionTypeCreateForm [name="create"]').click();
    });

    test('change the type of a motion', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        const motionList = new AdminMotionListPage(page);
        await page.locator('#motionListLink').click();
        await motionList.gotoMotionEdit(2);
        await expect(page.locator('.alert-success')).not.toBeVisible();
        await expect(page.locator(`#motionType option[value="${COMPATIBLE}"]`)).toBeAttached();
        await expect(page.locator(`#motionType option[value="${COMPATIBLE + 1}"]`)).not.toBeAttached();
        const selected = await page.evaluate(() => (document.getElementById('motionType') as HTMLSelectElement).value);
        expect(selected).toEqual('1');
        await page.evaluate((val) => {
            (document.getElementById('motionType') as HTMLSelectElement).value = val;
        }, String(COMPATIBLE));
        await page.locator('#motionUpdateForm [name="save"]').click();
        await expect(page.locator('.alert-success')).toBeVisible();

        await page.locator('#sidebar .view').click();
        await expect(page.locator('h2')).toContainText('NEW MOTION TEXT');
    });

    test('change the type of a motion again', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        const motionList = new AdminMotionListPage(page);
        await page.locator('#motionListLink').click();
        await motionList.gotoMotionEdit(2);
        await expect(page.locator('.alert-success')).not.toBeVisible();
        await expect(page.locator(`#motionType option[value="${COMPATIBLE}"]`)).toBeAttached();
        await expect(page.locator(`#motionType option[value="${COMPATIBLE + 1}"]`)).not.toBeAttached();
        const selected = await page.evaluate(() => (document.getElementById('motionType') as HTMLSelectElement).value);
        expect(selected).toEqual(String(COMPATIBLE));
        await page.evaluate((val) => {
            (document.getElementById('motionType') as HTMLSelectElement).value = val;
        }, '1');
        await page.locator('#motionUpdateForm [name="save"]').click();
        await expect(page.locator('.alert-success')).toBeVisible();

        await page.locator('#sidebar .view').click();
        await expect(page.locator('h2')).not.toContainText('NEW MOTION TEXT');
        await expect(page.locator('h2')).toContainText('ANTRAGSTEXT');
    });
});