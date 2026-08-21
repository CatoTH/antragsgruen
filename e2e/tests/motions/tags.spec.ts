import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { MotionPage } from '../../pages/MotionPage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';

const MOTION_SLUG = '321-o-zapft-is';

async function createTags(page: import('@playwright/test').Page): Promise<void> {
    const consultation = new AdminConsultationPage(page);
    await consultation.open();

    await page.locator('#tagsEditForm .tagAdderBtn').click();
    await page.locator('#tagsEditForm .newTagRowTemplate .tagTitle input').fill('Economy');
    await consultation.saveForm();
    await expect(page.locator('#tagsEditForm input[value="Economy"]')).toHaveCount(1);

    await page.locator('#tagsEditForm .tagAdderBtn').click();
    await page.locator('#tagsEditForm .newTagRowTemplate .tagTitle input').fill('Environment');
    await consultation.saveForm();
    await expect(page.locator('#tagsEditForm input[value="Economy"]')).toHaveCount(1);
    await expect(page.locator('#tagsEditForm input[value="Environment"]')).toHaveCount(1);
}

test.describe('Motion tags', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('tags are not shown before any are assigned', async ({ page }) => {
        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });
        await expect(page.locator('body')).not.toContainText('Themenbereich');
    });

    test('tags are only visible to admins after being created', async ({ page }) => {
        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });
        await loginAsStdAdmin(page);
        await createTags(page);

        await motion.open({ motionSlug: MOTION_SLUG });
        await logout(page);
        await motion.open({ motionSlug: MOTION_SLUG });
        await expect(page.locator('body')).not.toContainText('Themenbereich');

        await loginAsStdAdmin(page);
        await expect(page.locator('body')).toContainText('Themenbereich');
    });

    test('an admin can add and delete tags on a motion', async ({ page }) => {
        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });
        await loginAsStdAdmin(page);
        await createTags(page);
        await motion.open({ motionSlug: MOTION_SLUG });

        await expect(page.locator('#tagAdderForm')).toHaveCount(0);
        await page.locator('.tagAdderHolder').click();
        await expect(page.locator('#tagAdderForm')).toBeVisible();
        await page.locator('#tagAdderForm select').selectOption({ label: 'Environment' });
        await page.locator('#tagAdderForm [name="addTag"]').click();

        await expect(page.locator('.motionDataTable .tags')).toContainText('Environment');
        await expect(page.locator('#tagAdderForm')).toHaveCount(0);

        await page.locator('.tagAdderHolder').click();
        await expect(page.locator('#tagAdderForm')).toBeVisible();
        await page.locator('#tagAdderForm select').selectOption({ label: 'Verkehr' });
        await page.locator('#tagAdderForm [name="addTag"]').click();

        await expect(page.locator('.motionDataTable .tags')).toContainText('Verkehr');
        await expect(page.locator('#tagAdderForm')).toHaveCount(0);

        await expect(page.locator('.motionDataTable .tags .delTag2')).toBeVisible();
        await page.locator('.motionDataTable .tags .delTag2 [name="delTag"]').click();
        await expect(page.locator('.motionDataTable .tags')).not.toContainText('Verkehr');
        await expect(page.locator('.motionDataTable .tags .delTag2')).toHaveCount(0);
        await expect(page.locator('.motionDataTable .tags')).toContainText('Environment');
    });
});
