import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsProgressAdmin, logout, loginAsProgressAdmin as _loginAsProgressAdmin } from '../../utils/auth';
import { FIRST_FREE_MOTION_SECTION, FIRST_FREE_MOTION_TYPE } from '../../utils/constants';
import { dispatchClick } from '../../utils/dom';

test.describe('Merging: resolution with progress report', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create progress report motion type and edit it', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);
        await page.goto('/stdparteitag/std-parteitag/admin/motiontypes/index');

        await test.step('create a progress report motion type', async () => {
            await page.locator('.motionTypeCreate a').click();
            await page.locator('.presetProgress').first().check();
            await page.locator('#typeTitleSingular').first().fill('Progress report');
            await page.locator('#typeTitlePlural').first().fill('Progress reports');
            await page.locator('#typeCreateTitle').first().fill('Create');
            await page.locator('.motionTypeCreateForm [name="create"]').click();

            await page.goto('/stdparteitag/std-parteitag/admin/motiontypes/index');
        });

        await test.step('remove the second text from motions to make it compatible with progress reports', async () => {
            await page.locator('.motionType1').click();
            await dispatchClick(page, '.section4 .remover');
            await page.locator('.bootbox .btn-primary').click();
            await page.locator('.adminTypeForm [name="save"]').first().click();
            await expect(page.locator('.section3').first()).toBeVisible();
            await expect(page.locator('.section4').filter({ visible: true })).toHaveCount(0);

            await page.goto('/stdparteitag/std-parteitag/motion/321-o-zapft-is');
        });

        await test.step('merge the amendments and create the report', async () => {
            await page.locator('.sidebarActions .mergeamendments a').click();
            await page.locator('.mergeAllRow .btn-primary').click();
            await page.waitForTimeout(500);
            await expect(page.locator('.ice-ins')).toContainText('Oamoi a Maß');

            await page.evaluate(() => {
                document.querySelectorAll('.none').forEach((el) => el.remove());
                document.querySelectorAll('#draftSavingPanel').forEach((el) => el.remove());
            });
            await page.waitForTimeout(1000);
            await page.locator('.motionMergeForm [name="save"]').click();

            await page.locator("input[name='newStatus'][value='resolution_final']").first().check();
            await expect(page.locator('#newInitiator').first()).toBeVisible();
            await expect(page.locator('#dateResolution').first()).toBeVisible();
            await expect(page.locator('#motionType').first()).toBeVisible();
            await page.locator('#newInitiator').first().fill('Mitgliedervollversammlung');
            await page.locator('#dateResolution').first().fill('23.04.2017');
            await page.locator('#motionType').first().selectOption(String(FIRST_FREE_MOTION_TYPE));

            await page.locator('#motionConfirmForm [name="confirm"]').click();

            await expect(page.locator('body')).toContainText('Der Antrag wurde überarbeitet');
            await page.locator('#motionConfirmedForm [type="submit"]').click();

            await expect(page.locator('h1')).toContainText('O\u2019zapft is!');
        });

        await test.step('confirm the progress report was created', async () => {
            await expect(page.locator('.motionDataTable')).toContainText('Beschluss durch');
            await expect(page.locator('.motionDataTable')).toContainText('Mitgliedervollversammlung');
        });

        await test.step('not see the progress report as regular user', async () => {
            await expect(page.locator('h2').filter({ hasText: 'Beschlusstext' }).first()).toBeVisible();
            await expect(page.locator('#section_53').first()).toBeVisible();
        });

        await test.step('edit the progress report as admin', async () => {
            await expect(page.locator('h2').filter({ hasText: 'Sachstand' }).first()).toBeVisible();

            await logout(page);
            await expect(page.locator('h2').filter({ hasText: 'Beschlusstext' }).first()).toBeVisible();
            await expect(page.locator('h2').getByText('Sachstand').filter({ visible: true })).toHaveCount(0);

            await loginAsProgressAdmin(page);
            await expect(page.locator('h2').filter({ hasText: 'Sachstand' }).first()).toBeVisible();
            await page.evaluate(() => {
                const btn = document.querySelector('.editorialEditForm .editCaller') as HTMLElement | null;
                if (btn) btn.click();
            });
            await page.waitForTimeout(500);

            const sectionId = `section_${FIRST_FREE_MOTION_SECTION + 2}`;
            await page.evaluate(
                ({ id }) => {
                    const w = window as any;
                    w.CKEDITOR.instances[`${id}_content`].setData(
                        '<p>Famous quote</p><blockquote>So Long, and Thanks for All the Fish</blockquote>',
                    );
                },
                { id: sectionId },
            );
            await page.locator(`#${sectionId} .metadataEdit input.author`).first().fill('You know who');
            await dispatchClick(page, '.saveRow .submitBtn');
            await page.waitForTimeout(500);
            await expect(page.locator('body')).toContainText('You know who, Heute');

            await logout(page);
            await expect(page.locator('blockquote')).toContainText('So Long, and Thanks for All the Fish');
            await expect(page.locator('body')).toContainText('You know who, Heute');
            await expect(page.locator('.editorialEditForm .editCaller').filter({ visible: true })).toHaveCount(0);
        });

    });
});
