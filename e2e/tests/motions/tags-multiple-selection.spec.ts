import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { FIRST_FREE_MOTION_ID } from '../../utils/constants';
import { ConsultationHomePage } from '../../pages/BasePage';
import { MotionPage } from '../../pages/MotionPage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';

const MOTION_SLUG = '321-o-zapft-is';

test.describe('Multiple tag selection', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('a motion can be created with multiple tags', async ({ page }) => {
        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });
        await expect(page.locator('body')).not.toContainText('Themenbereich');

        await loginAsStdAdmin(page);
        const consultation = new AdminConsultationPage(page);
        await consultation.open();
        await expect(page.locator('#allowMultipleTags')).not.toBeChecked();
        await page.locator('#allowMultipleTags').check();
        await consultation.saveForm();
        await expect(page.locator('#allowMultipleTags')).toBeChecked();

        const home = new ConsultationHomePage(page);
        await home.open();
        const createPage = await home.gotoMotionCreatePage();

        await expect(page.locator('.multipleTagsGroup')).toContainText('Umwelt');
        await expect(page.locator('.multipleTagsGroup')).toContainText('Verkehr');
        await page.locator("input[name='tags[]'][value='1']").check();
        await page.locator("input[name='tags[]'][value='10']").check();

        await createPage.fillInValidSampleData('Testantrag 1', false);
        await createPage.saveForm();
        await page.locator('#motionConfirmForm [name="confirm"]').click();

        await home.open();
        await home.gotoMotionView(FIRST_FREE_MOTION_ID);
        await expect(page.locator('.tags')).toContainText('Umwelt');
        await expect(page.locator('.tags')).toContainText('Soziales');
        await expect(page.locator('.tags')).not.toContainText('Verkehr');
    });
});
