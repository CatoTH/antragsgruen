import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/BasePage';
import { FIRST_FREE_MOTION_ID } from '../../utils/constants';

test.describe('Merging: Create resolution with protocol', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create resolution with protocol and verify diff/history', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('.motionListStd .motionLink2')).toBeVisible();
        await expect(page.locator('.resolutionList')).toHaveCount(0);

        await page.locator('.motionLink2').click();
        await expect(page.locator('.sidebarActions .mergeamendments')).toHaveCount(0);

        await loginAsStdAdmin(page);
        await page.locator('.sidebarActions .mergeamendments a').click();
        await page.locator('.toMergeAmendments #markAmendment1').check();
        await page.locator('.mergeAllRow .btn-primary').click();
        await page.waitForTimeout(500);
        await expect(page.locator('.ice-ins')).toContainText('Oamoi a Maß');

        await page.evaluate(() => {
            const w = window as any;
            w.CKEDITOR.instances.protocol_text_wysiwyg.setData(
                '<p>Famous quote</p><blockquote>So Long, and Thanks for All the Fish</blockquote>',
            );
        });
        await page.locator("input[name='protocol_public'][value='1']").check();

        await page.evaluate(() => {
            document.querySelectorAll('.none').forEach((el) => el.remove());
            document.querySelectorAll('#draftSavingPanel').forEach((el) => el.remove());
        });
        await page.waitForTimeout(1000);
        await page.locator('.motionMergeForm [name="save"]').click();

        await expect(page.locator('body')).toContainText('Oamoi a Maß');
        await expect(page.locator('.inserted')).not.toContainText('Oamoi a Maß');
        await page.evaluate(() => {
            const btn = (document.querySelector(
                'input[name="diffStyle"][value="diff"]',
            ) as HTMLElement | null)?.closest('.btn') as HTMLElement | null;
            if (btn) btn.click();
        });
        await expect(page.locator('.inserted')).toContainText('Oamoi a Maß');

        await page.locator("input[name='newStatus'][value='resolution_preliminary']").check();
        await expect(page.locator('#newInitiator')).toBeVisible();
        await expect(page.locator('#dateResolution')).toBeVisible();
        await page.locator('#newInitiator').fill('Mitgliedervollversammlung');
        await page.locator('#dateResolution').fill('23.04.2017');

        await page.locator('#motionConfirmForm [name="confirm"]').click();

        await expect(page.locator('body')).toContainText('Der Antrag wurde überarbeitet');
        await page.locator('#motionConfirmedForm [type="submit"]').click();

        await expect(page.locator('h1')).not.toContainText('A2neu');
        await expect(page.locator('h1')).toContainText('O\u2019zapft is!');
        await expect(page.locator('body')).toContainText('Oamoi a Maß');

        await expect(page.locator('.motionDataTable')).toContainText('Beschluss durch');
        await expect(page.locator('.motionDataTable')).toContainText('Beschlossen am');
        await expect(page.locator('.motionDataTable')).toContainText('Mitgliedervollversammlung');
        await expect(page.locator('.motionDataTable')).toContainText('Beschluss (vorläufig)');

        await expect(page.locator('h2')).toContainText('Beschlusstext');

        await expect(page.locator('.protocolHolder')).toHaveCount(0);
        await expect(page.locator('body')).not.toContainText('So Long, and Thanks for All the Fish');
        await page.evaluate(() => {
            const btn = document.querySelector('.motionProtocol .protocolOpener') as HTMLElement | null;
            if (btn) btn.click();
        });
        await expect(page.locator('.protocolHolder')).toContainText('So Long, and Thanks for All the Fish');

        await page.locator('.motionDataTable .btnHistoryOpener').click();
        await page.locator('.changesLink a').click();
        await expect(page.locator('.motionChangeView.section2 .inserted')).toContainText('Oamoi a Maß');
        await expect(page.locator('.motionChangeView .section3')).toHaveCount(0);
        await expect(page.locator('.protocolHolder')).toContainText('So Long, and Thanks for All the Fish');

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('.resolutionList')).toBeVisible();
        await expect(
            page.locator(`.resolutionList .motionLink${FIRST_FREE_MOTION_ID + 1}`),
        ).toBeVisible();
        await expect(page.locator('.motionListStd .motionLink2')).toBeVisible();
    });
});
