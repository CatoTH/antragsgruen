import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { FIRST_FREE_MOTION_ID } from '../../utils/constants';

test.describe('Appearance: resolutions displayed separately', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('resolutions separate from motions across all start layouts', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);

        await page.locator('.motionLink2').click();
        await page.locator('.motionData').waitFor();

        await page.locator('.sidebarActions .mergeamendments a').click();
        await page.locator('.mergeAllRow .btn-primary').click();
        await page.waitForTimeout(500);

        await expect(page.locator('.ice-ins')).toContainText('Oamoi a Maß');
        await page.locator('.motionMergeForm [name="save"]').click();

        await page.locator("input[name='newStatus'][value='resolution_final']").click();
        await expect(page.locator('#newInitiator')).toBeVisible();
        await expect(page.locator('#dateResolution')).toBeVisible();
        await page.locator('#newInitiator').fill('Mitgliedervollversammlung');
        await page.locator('#dateResolution').fill('23.04.2017');
        await page.locator('#motionConfirmForm [name="confirm"]').click();

        await expect(page.locator('body')).toContainText('Der Antrag wurde überarbeitet');

        await page.goto('/stdparteitag/std-parteitag');
        await expect(
            page.locator(
                `.sectionResolutions .motionLink${FIRST_FREE_MOTION_ID + 1}`,
            ),
        ).toContainText('O’zapft is!');
        await expect(page.locator('.motionLink2')).toContainText('O’zapft is!');
        await expect(page.locator('.sectionResolutions')).not.toContainText('A2');

        const layouts: Array<[string, string]> = [
            ['0', 'START_LAYOUT_STANDARD'],
            ['1', 'START_LAYOUT_AGENDA'],
            ['2', 'START_LAYOUT_DISCUSSION_TAGS'],
            ['3', 'START_LAYOUT_TAGS'],
            ['4', 'START_LAYOUT_AGENDA_HIDE_AMEND'],
        ];

        for (const [layoutId, _name] of layouts) {
            await page.goto('/stdparteitag/std-parteitag/admin/index/appearance');
            await page.locator('#startLayoutType').selectOption(layoutId);
            await page.evaluate(() => {
                const el = document.querySelector(
                    '#showResolutionsCombined',
                ) as HTMLInputElement;
                if (el) {
                    el.checked = false;
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
            await expect(page.locator('.showResolutionsSeparateHolder')).toBeVisible();
            await page.evaluate(() => {
                const el = document.querySelector(
                    'input[name="settings[showResolutionsSeparateMode]"][value="1"]',
                ) as HTMLInputElement;
                if (el) {
                    el.checked = true;
                }
            });
            await page.locator('#consultationAppearanceForm [name="save"]').click();

            await page.goto('/stdparteitag/std-parteitag');
            await expect(page.locator('.motionLink2')).toBeVisible();
            await expect(
                page.locator(`.motionLink${FIRST_FREE_MOTION_ID + 1}`),
            ).toHaveCount(0);

            await page.locator('#sidebarResolutions').click();
            await expect(page.locator('h1')).toContainText('Beschlüsse');
            await expect(page.locator('.motionLink2')).toHaveCount(0);
            await expect(
                page.locator(`.motionLink${FIRST_FREE_MOTION_ID + 1}`),
            ).toBeVisible();
            if (layoutId !== '3' && layoutId !== '2') {
                await expect(page.locator('body')).not.toContainText('A2');
            }

            await page.goto('/stdparteitag/std-parteitag/admin/index/appearance');
            await page.locator('#startLayoutType').selectOption(layoutId);
            await page.evaluate(() => {
                const el = document.querySelector(
                    'input[name="settings[showResolutionsSeparateMode]"][value="2"]',
                ) as HTMLInputElement;
                if (el) {
                    el.checked = true;
                }
            });
            await page.locator('#consultationAppearanceForm [name="save"]').click();

            await page.goto('/stdparteitag/std-parteitag');
            await expect(page.locator('.green')).toContainText('Beschlüsse');
            await expect(page.locator('.motionLink2')).toHaveCount(0);
            await expect(
                page.locator(`.motionLink${FIRST_FREE_MOTION_ID + 1}`),
            ).toBeVisible();

            if (layoutId === '3') {
                await expect(page.locator('.motionTable')).toContainText('O’zapft is!');
            } else if (layoutId === '2') {
                await expect(page.locator('.motionList')).toContainText('O’zapft is!');
            } else {
                await expect(page.locator('.motionList')).toContainText('O’zapft is!');
                await expect(page.locator('.motionList')).not.toContainText('A2');
            }

            await page.locator('#sidebarMotions').click();
            await expect(page.locator('h1')).toContainText('Anträge');
            await expect(page.locator('.motionLink2')).toBeVisible();
            await expect(
                page.locator(`.motionLink${FIRST_FREE_MOTION_ID + 1}`),
            ).toHaveCount(0);
        }
    });
});
