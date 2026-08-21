import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsProgressAdmin, logout, loginAsProgressAdmin as _loginAsProgressAdmin } from '../../utils/auth';
import { FIRST_FREE_MOTION_SECTION, FIRST_FREE_MOTION_TYPE } from '../../utils/constants';

test.describe('Merging: resolution with progress report', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create progress report motion type and edit it', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);
        await page.goto('/stdparteitag/std-parteitag/admin/motiontypes/index');

        await page.locator('.motionTypeCreate a').click();
        await page.locator('.presetProgress').check();
        await page.locator('#typeTitleSingular').fill('Progress report');
        await page.locator('#typeTitlePlural').fill('Progress reports');
        await page.locator('#typeCreateTitle').fill('Create');
        await page.locator('.motionTypeCreateForm [name="create"]').click();

        await page.goto('/stdparteitag/std-parteitag/admin/motiontypes/index');
        await page.locator('.motionType1').click();
        await page.locator('.section4 .remover').click();
        await page.locator('.bootbox .btn-primary').click();
        await page.locator('.adminTypeForm [name="save"]').click();
        await expect(page.locator('.section3')).toBeVisible();
        await expect(page.locator('.section4')).toHaveCount(0);

        await page.goto('/stdparteitag/std-parteitag/motion/321-o-zapft-is');
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

        await page.locator("input[name='newStatus'][value='resolution_final']").check();
        await expect(page.locator('#newInitiator')).toBeVisible();
        await expect(page.locator('#dateResolution')).toBeVisible();
        await expect(page.locator('#motionType')).toBeVisible();
        await page.locator('#newInitiator').fill('Mitgliedervollversammlung');
        await page.locator('#dateResolution').fill('23.04.2017');
        await page.locator('#motionType').selectOption(String(FIRST_FREE_MOTION_TYPE));

        await page.locator('#motionConfirmForm [name="confirm"]').click();

        await expect(page.locator('body')).toContainText('Der Antrag wurde überarbeitet');
        await page.locator('#motionConfirmedForm [type="submit"]').click();

        await expect(page.locator('h1')).toContainText('O\u2019zapft is!');
        await expect(page.locator('.motionDataTable')).toContainText('Beschluss durch');
        await expect(page.locator('.motionDataTable')).toContainText('Mitgliedervollversammlung');

        await expect(page.locator('h2')).toContainText('Beschlusstext');
        await expect(page.locator('#section_53')).toBeVisible();
        await expect(page.locator('h2')).toContainText('Sachstand');

        await logout(page);
        await expect(page.locator('h2')).toContainText('Beschlusstext');
        await expect(page.locator('h2')).not.toContainText('Sachstand');

        await loginAsProgressAdmin(page);
        await expect(page.locator('h2')).toContainText('Sachstand');
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
        await page.locator(`#${sectionId} .metadataEdit input.author`).fill('You know who');
        await page.locator('.saveRow .submitBtn').click();
        await page.waitForTimeout(500);
        await expect(page.locator('body')).toContainText('You know who, Heute');

        await logout(page);
        await expect(page.locator('blockquote')).toContainText('So Long, and Thanks for All the Fish');
        await expect(page.locator('body')).toContainText('You know who, Heute');
        await expect(page.locator('.editorialEditForm .editCaller')).toHaveCount(0);
    });
});
