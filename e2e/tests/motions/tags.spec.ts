import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { MotionPage } from '../../pages/MotionPage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';
import { dispatchClick } from '../../utils/dom';

const MOTION_SLUG = '321-o-zapft-is';

async function createTags(page: import('@playwright/test').Page): Promise<void> {
    const consultation = new AdminConsultationPage(page);
    await consultation.open();

    await dispatchClick(page, '#tagsEditForm .tagAdderBtn');
    await page.locator('#tagsEditForm .newTagRowTemplate .tagTitle input').first().fill('Economy');
    await consultation.saveForm();
    await expect(page.locator('#tagsEditForm input[value="Economy"]')).toHaveCount(1);

    await dispatchClick(page, '#tagsEditForm .tagAdderBtn');
    await page.locator('#tagsEditForm .newTagRowTemplate .tagTitle input').first().fill('Environment');
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
        await test.step('Ensure tags are not visible yet', async () => {
            await expect(page.locator('body')).not.toContainText('Themenbereich', { useInnerText: true });
        });
    });

    test('tags are only visible to admins after being created', async ({ page }) => {
        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });
        await loginAsStdAdmin(page);
        await createTags(page);

        await motion.open({ motionSlug: MOTION_SLUG });
        await logout(page);
        await motion.open({ motionSlug: MOTION_SLUG });
        await expect(page.locator('body')).not.toContainText('Themenbereich', { useInnerText: true });

        await loginAsStdAdmin(page);
        await expect(page.locator('body')).toContainText('Themenbereich');
    });

    test('an admin can add and delete tags on a motion', async ({ page }) => {
        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });
        await loginAsStdAdmin(page);
        await createTags(page);
        await motion.open({ motionSlug: MOTION_SLUG });

        await expect(page.locator('#tagAdderForm').filter({ visible: true })).toHaveCount(0);
        await page.locator('.tagAdderHolder').click();
        await expect(page.locator('#tagAdderForm').first()).toBeVisible();
        await test.step('Create some tags', async () => {
            await page.locator('#tagAdderForm select').first().selectOption({ label: 'Environment' });
        });

        await test.step('See the motion logged out now', async () => {
            await page.locator('#tagAdderForm [name="addTag"]').click();

            await expect(page.locator('.motionDataTable .tags')).toContainText('Environment');
        });

        await test.step('See the motion as a admin user now', async () => {
            await expect(page.locator('#tagAdderForm').filter({ visible: true })).toHaveCount(0);

            await page.locator('.tagAdderHolder').click();
        });

        await test.step('Add a tag', async () => {
            await expect(page.locator('#tagAdderForm').first()).toBeVisible();
            await page.locator('#tagAdderForm select').first().selectOption({ label: 'Verkehr' });
            await page.locator('#tagAdderForm [name="addTag"]').click();

            await expect(page.locator('.motionDataTable .tags')).toContainText('Verkehr');
            await expect(page.locator('#tagAdderForm').filter({ visible: true })).toHaveCount(0);
        });

        await test.step('Delete a tag', async () => {
            await expect(page.locator('.motionDataTable .tags .delTag2').first()).toBeVisible();
            await page.locator('.motionDataTable .tags .delTag2 [name="delTag"]').click();
            await expect(page.locator('.motionDataTable .tags').getByText('Verkehr').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.motionDataTable .tags .delTag2').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.motionDataTable .tags')).toContainText('Environment');
        });
    });
});
