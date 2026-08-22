import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { expectBootboxDialog, acceptBootbox } from '../../utils/dom';
import { FIRST_FREE_MOTION_ID } from '../../utils/constants';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';

const SUPPORT_GIVEN_BY_INITIATOR = '1';
const PERSON_NATURAL = '0';
const PERSON_ORGANIZATION = '1';

async function enableSupporters(
    page: import('@playwright/test').Page,
    minSupporters: number,
    allowMore: boolean,
): Promise<void> {
    const motionType = new AdminMotionTypePage(page);
    await motionType.open({ motionTypeId: 1 });
    await page.locator('#typeSupportType').selectOption(SUPPORT_GIVEN_BY_INITIATOR);
    await page.locator('#typeMinSupporters').fill(String(minSupporters));
    if (allowMore) {
        await page.locator('#typeAllowMoreSupporters').check();
        await page.locator('#typeHasOrga').check();
    } else {
        await page.locator('#typeHasOrga').uncheck();
    }
    await motionType.saveForm();
}

test.describe('Motion supporters', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('supporters are disabled by default', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionCreatePage();
        await expect(page.locator('.supporterData')).toHaveCount(0);
    });

    test('a motion can be created once supporters are enabled', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);
        await enableSupporters(page, 0, false);

        await home.open();
        const createPage = await home.gotoMotionCreatePage();
        await expect(page.locator('.supporterData')).toBeVisible();
        await expect(page.locator('.supporterData input.name')).toBeVisible();
        await expect(page.locator('.supporterData input.organization')).toHaveCount(0);

        await createPage.fillInValidSampleData('Sample motion with supporters');
        await createPage.saveForm();
        await expect(page.locator('h1')).toContainText(/antrag bestätigen/i);
    });

    test('person and organization toggling shows the right fields', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);
        await enableSupporters(page, 2, true);
        await expect(page.locator('#typeMinSupporters')).toHaveValue('2');

        await home.open();
        await logout(page);
        await home.gotoMotionCreatePage();

        await expect(page.locator('.supporterDataHead')).toBeVisible();
        await expect(page.locator('.supporterData')).toBeVisible();
        await expect(page.locator('#initiatorOrga')).toBeVisible();
        await expect(page.locator('#resolutionDate')).toHaveCount(0);

        await page.locator('#personTypeOrga').selectOption(PERSON_ORGANIZATION);
        await expect(page.locator('.supporterDataHead')).toHaveCount(0);
        await expect(page.locator('.supporterData')).toHaveCount(0);
        await expect(page.locator('#initiatorOrga')).toHaveCount(0);
        await expect(page.locator('#resolutionDate')).toBeVisible();

        await page.locator('#personTypeNatural').selectOption(PERSON_NATURAL);
        await expect(page.locator('.supporterDataHead')).toBeVisible();
        await expect(page.locator('.supporterData')).toBeVisible();
        await expect(page.locator('#initiatorOrga')).toBeVisible();
        await expect(page.locator('#resolutionDate')).toHaveCount(0);
    });

    test('submitting without enough supporters is rejected', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);
        await enableSupporters(page, 2, true);

        await home.open();
        await logout(page);
        const createPage = await home.gotoMotionCreatePage();

        await createPage.fillInValidSampleData('Another sample motion with supporters');
        await createPage.saveForm();

        await expect(page.locator('h1')).not.toContainText(/antrag bestätigen/i);
        await expectBootboxDialog(
            page,
            /Es müssen mindestens 2 Unterstützer\*innen angegeben werden/,
        );
        await acceptBootbox(page);
    });

    test('supporters can be edited, corrected and submitted with the motion', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);
        await enableSupporters(page, 2, true);

        await home.open();
        await logout(page);
        const createPage = await home.gotoMotionCreatePage();
        await createPage.fillInValidSampleData('Another sample motion with supporters');
        await createPage.saveForm();
        await acceptBootbox(page);

        const rowCount = await page.evaluate(() => {
            const w = window as any;
            w.$('.supporterData .supporterRow').eq(1).remove();
            w.$('.supporterData .supporterRow').eq(1).remove();
            w.$('.supporterData .adderRow button').click();
            return w.$('.supporterData .supporterRow').length;
        });
        expect(rowCount).toBe(2);

        await page.evaluate(() => {
            const w = window as any;
            const rows = w.$('.supporterData .supporterRow');
            rows.eq(0).find('input.name').val('Name 1');
            rows.eq(0).find('input.organization').val('Orga 1');
            rows.eq(1).find('input.name').val('Name 2');
        });
        await createPage.saveForm();

        await expect(page.locator('h1')).toContainText(/antrag bestätigen/i);
        await expect(page.locator('body')).toContainText('Name 1');
        await expect(page.locator('body')).toContainText('Orga 1');
        await expect(page.locator('body')).toContainText('Name 2');

        await page.locator('#motionConfirmForm [name="modify"]').click();

        const values = await page.evaluate(() => {
            const w = window as any;
            const rows = w.$('.supporterData .supporterRow');
            return {
                name1: rows.eq(0).find('input.name').val(),
                name2: rows.eq(1).find('input.name').val(),
                orga1: rows.eq(0).find('input.organization').val(),
            };
        });
        expect(values.name1).toBe('Name 1');
        expect(values.name2).toBe('Name 2');
        expect(values.orga1).toBe('Orga 1');

        await page.evaluate(() => {
            const w = window as any;
            const rows = w.$('.supporterData .supporterRow');
            rows.eq(0).find('input.name').val('Person 1');
            rows.eq(0).find('input.organization').val('Organization 1');
            rows.eq(1).find('input.name').val('Person 2');
        });
        await createPage.saveForm();

        await expect(page.locator('h1')).toContainText(/antrag bestätigen/i);
        await expect(page.locator('body')).not.toContainText('Name 1');
        await expect(page.locator('body')).not.toContainText('Orga 1');
        await expect(page.locator('body')).not.toContainText('Name 2');
        await expect(page.locator('body')).toContainText('Person 1');
        await expect(page.locator('body')).toContainText('Organization 1');
        await expect(page.locator('body')).toContainText('Person 2');

        await page.locator('#motionConfirmForm [name="confirm"]').click();
        await expect(page.locator('h1')).toContainText(/antrag veröffentlicht/i);
        await page.locator('#motionConfirmedForm [type="submit"]').click();

        await expect(page.locator('body')).toContainText('Another sample motion with supporters');
        await expect(page.locator('body')).toContainText('Mein Name');

        await page.locator(`.motionLink${FIRST_FREE_MOTION_ID + 1}`).click();
        await expect(page.locator('.motionData')).toContainText('Mein Name');
        await expect(page.locator('.supporters')).toContainText('Person 1');
        await expect(page.locator('.supporters')).toContainText('Organization 1');
        await expect(page.locator('.supporters')).toContainText('Person 2');
    });
});
