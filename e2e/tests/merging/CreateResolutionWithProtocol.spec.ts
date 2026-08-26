import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { FIRST_FREE_MOTION_ID } from '../../utils/constants';
import { dispatchClick } from '../../utils/dom';

test.describe('Merging: Create resolution with protocol', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create resolution with protocol and verify diff/history', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('.motionListStd .motionLink2').first()).toBeVisible();
        await expect(page.locator('.resolutionList').filter({ visible: true })).toHaveCount(0);

        await page.locator('.motionLink2').click();
        await expect(page.locator('.sidebarActions .mergeamendments').filter({ visible: true })).toHaveCount(0);

        await loginAsStdAdmin(page);
        await test.step('merge the amendments', async () => {
            await page.locator('.sidebarActions .mergeamendments a').click();
            await page.locator('.toMergeAmendments #markAmendment1').first().check();
            await page.locator('.mergeAllRow .btn-primary').click();
            await page.waitForTimeout(500);
        });

        await expect(page.locator('.ice-ins')).toContainText('Oamoi a Maß');

        await page.evaluate(() => {
            const w = window as any;
            w.CKEDITOR.instances.protocol_text_wysiwyg.setData(
                '<p>Famous quote</p><blockquote>So Long, and Thanks for All the Fish</blockquote>',
            );
        });
        await page.locator("input[name='protocol_public'][value='1']").first().check();

        await page.evaluate(() => {
            document.querySelectorAll('.none').forEach((el) => el.remove());
            document.querySelectorAll('#draftSavingPanel').forEach((el) => el.remove());
        });
        await page.waitForTimeout(1000);
        await page.locator('.motionMergeForm [name="save"]').click();

        await expect(page.locator('body')).toContainText('Oamoi a Maß');
        await expect(page.locator('.inserted').getByText('Oamoi a Maß').filter({ visible: true })).toHaveCount(0);
        await page.evaluate(() => {
            const btn = (document.querySelector(
                'input[name="diffStyle"][value="diff"]',
            ) as HTMLElement | null)?.closest('.btn') as HTMLElement | null;
            if (btn) btn.click();
        });
        await expect(page.locator('.inserted')).toContainText('Oamoi a Maß');

        await page.locator("input[name='newStatus'][value='resolution_preliminary']").first().check();
        await test.step('choose the resolution', async () => {
            await expect(page.locator('#newInitiator').first()).toBeVisible();
            await expect(page.locator('#dateResolution').first()).toBeVisible();
            await page.locator('#newInitiator').first().fill('Mitgliedervollversammlung');
            await page.locator('#dateResolution').first().fill('23.04.2017');

            await page.locator('#motionConfirmForm [name="confirm"]').click();

            await expect(page.locator('body')).toContainText('Der Antrag wurde überarbeitet');
            await page.locator('#motionConfirmedForm [type="submit"]').click();
        });

        await test.step('confirm the resolution', async () => {
            await expect(page.locator('h1').getByText('A2neu').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('h1')).toContainText('O\u2019zapft is!');
            await expect(page.locator('body')).toContainText('Oamoi a Maß');

            await expect(page.locator('.motionDataTable')).toContainText('Beschluss durch');
            await expect(page.locator('.motionDataTable')).toContainText('Beschlossen am');
            await expect(page.locator('.motionDataTable')).toContainText('Mitgliedervollversammlung');
            await expect(page.locator('.motionDataTable')).toContainText('Beschluss (vorläufig)');

            await expect(page.locator('h2').filter({ hasText: 'Beschlusstext' }).first()).toBeVisible();

            await expect(page.locator('.protocolHolder').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('body')).not.toContainText('So Long, and Thanks for All the Fish', { useInnerText: true });
            await page.evaluate(() => {
                const btn = document.querySelector('.motionProtocol .protocolOpener') as HTMLElement | null;
                if (btn) btn.click();
            });
            await expect(page.locator('.protocolHolder')).toContainText('So Long, and Thanks for All the Fish');
        });

        await test.step('see the diff view', async () => {
            await dispatchClick(page, '.motionDataTable .btnHistoryOpener');
            await page.locator('.changesLink a').click();
            await expect(page.locator('.motionChangeView.section2 .inserted')).toContainText('Oamoi a Maß');
            await expect(page.locator('.motionChangeView .section3').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.protocolHolder')).toContainText('So Long, and Thanks for All the Fish');

            await page.goto('/stdparteitag/std-parteitag');
            await expect(page.locator('.resolutionList').first()).toBeVisible();
            await expect(
                page.locator(`.resolutionList .motionLink${FIRST_FREE_MOTION_ID + 1}`),
            ).toBeVisible();
            await expect(page.locator('.motionListStd .motionLink2').first()).toBeVisible();
        });

    });
});
