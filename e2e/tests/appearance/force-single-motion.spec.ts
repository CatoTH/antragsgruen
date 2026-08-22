import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';

test.describe('Appearance: force single motion', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enable single-motion mode, draft, re-submit and merge', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/admin/consultation');
        await expect(page.locator('#forceMotionRow')).toHaveCount(0);
        await expect(page.locator('#singleMotionMode')).not.toBeChecked();

        await page.locator('#singleMotionMode').check();
        await expect(page.locator('#forceMotionRow')).toBeVisible();
        await page.locator('#forceMotion').selectOption('3');
        await page.locator('#consultationSettingsForm [name="save"]').click();

        await expect(page.locator('#forceMotionRow')).toBeVisible();
        await expect(page.locator('#singleMotionMode')).toBeChecked();

        await page.locator('.homeLinkLogo').click();
        await expect(page.locator('h1')).toContainText('A3: Textformatierungen');

        await logout(page);
        await page.evaluate(() => {
            const el = document.querySelector('.breadcrumb .pseudoLink');
            if (el) {
                el.dispatchEvent(
                    new MouseEvent('click', { bubbles: true, cancelable: true, view: window }),
                );
            }
        });
        await expect(page.locator('h1')).toContainText('A3: Textformatierungen');

        await loginAsStdAdmin(page);
        await page.locator('#sidebar .adminEdit a').click();
        await page.locator('#motionStatus').selectOption('1');
        await page.locator('#motionUpdateForm [name="save"]').click();

        await page.locator('.homeLinkLogo').click();
        await expect(page.locator('h1')).toContainText('A3: Textformatierungen');
        await expect(page.locator('.alertDraft')).toBeVisible();
        await expect(page.locator('#sidebar .adminEdit')).toBeVisible();

        await logout(page);
        await expect(page.locator('h1')).not.toContainText('A3: Textformatierungen');
        await expect(page.locator('body')).toContainText('Dieser Antrag kann nicht angezeigt werden.');
        await expect(page.locator('#sidebar li')).toHaveCount(0);

        await loginAsStdAdmin(page);
        await page.locator('#sidebar .adminEdit a').click();
        await page.locator('#motionStatus').selectOption('15');
        await page.locator('#motionUpdateForm [name="save"]').click();

        await page.locator('.homeLinkLogo').click();
        await expect(page.locator('h1')).toContainText('A3: Textformatierungen');

        await page.locator('#sidebar .mergeamendments a').click();
        await page.locator('.mergeAllRow [type="submit"]').click();

        await page.evaluate(() => {
            document.querySelectorAll('.none').forEach((el) => el.remove());
            document.querySelectorAll('#draftSavingPanel').forEach((el) => el.remove());
        });

        await page.locator('.motionMergeForm [name="save"]').click();
        await page.locator('#motionConfirmForm [name="confirm"]').click();

        await page.locator('.homeLinkLogo').click();
        await expect(page.locator('h1')).toContainText('Textformatierungen');
        await expect(page.locator('.motionHistory .currVersion')).toContainText('Version 2');
        await expect(page.locator('.statusRow')).toContainText('Beschluss');
    });
});
