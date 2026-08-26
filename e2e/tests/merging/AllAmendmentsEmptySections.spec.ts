import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';

test.describe('Merging: Empty sections handling', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('remove and re-add text in empty sections', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.gotoMotionView(2);
        await loginAsStdAdmin(page);
        await test.step('merge the amendments', async () => {
            await page.locator('.sidebarActions .mergeamendments a').click();
            await page.locator('.mergeAllRow .btn-primary').click();

            await page.waitForTimeout(1000);
        });

        await expect(page.locator('#sections_3_0_wysiwyg')).toContainText('I-Düpferl-Reita');
        await page.evaluate(() => {
            const w = window as any;
            w.CKEDITOR.instances.sections_3_0_wysiwyg.setData('<p>Replaced Text</p>');
        });
        await expect(page.locator('#sections_3_0_wysiwyg').getByText('I-Düpferl-Reita').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#sections_3_0_wysiwyg')).toContainText('Replaced Text');

        await page.evaluate(() => {
            const el = document.querySelector('.section3 .removeSection input') as HTMLElement | null as HTMLInputElement | null;
            if (el) {
                el.checked = true;
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        await page.evaluate(() => {
            document.querySelectorAll('.none').forEach((el) => el.remove());
            document.querySelectorAll('#draftSavingPanel').forEach((el) => el.remove());
        });
        await page.waitForTimeout(1000);

        await expect(page.locator('body')).not.toContainText('I-Düpferl-Reita', { useInnerText: true });
        await expect(page.locator('body')).not.toContainText('Replaced Text', { useInnerText: true });

        await page.locator('.motionMergeForm [name="save"]').click();

        await expect(page.locator('body')).not.toContainText('I-Düpferl-Reita', { useInnerText: true });
        await expect(page.locator('body')).not.toContainText('Replaced Text', { useInnerText: true });

        await page.locator('#motionConfirmForm [name="modify"]').click();

        await expect(page.locator('.section3 .removeSection input')).toBeChecked();
        await expect(page.locator('body')).not.toContainText('I-Düpferl-Reita', { useInnerText: true });
        await expect(page.locator('body')).not.toContainText('Replaced Text', { useInnerText: true });

        await page.evaluate(() => {
            const el = document.querySelector('.section3 .removeSection input') as HTMLElement | null as HTMLInputElement | null;
            if (el) {
                el.checked = false;
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await expect(page.locator('#sections_3_0_wysiwyg').getByText('I-Düpferl-Reita').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#sections_3_0_wysiwyg')).toContainText('Replaced Text');

        await page.evaluate(() => {
            const el = document.querySelector('.section3 .removeSection input') as HTMLElement | null as HTMLInputElement | null;
            if (el) {
                el.checked = true;
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await expect(page.locator('#sections_3_0_wysiwyg').getByText('I-Düpferl-Reita').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#sections_3_0_wysiwyg').getByText('Replaced Text').filter({ visible: true })).toHaveCount(0);

        await page.evaluate(() => {
            document.querySelectorAll('.none').forEach((el) => el.remove());
            document.querySelectorAll('#draftSavingPanel').forEach((el) => el.remove());
        });
        await page.waitForTimeout(1000);

        await page.locator('.motionMergeForm [name="save"]').click();

        await expect(page.locator('body')).not.toContainText('I-Düpferl-Reita', { useInnerText: true });
        await expect(page.locator('body')).not.toContainText('Replaced Text', { useInnerText: true });

        await page.locator('#motionConfirmForm [name="confirm"]').click();

        await page.evaluate(() => {
            const btn = document.querySelector('#motionConfirmedForm button') as HTMLElement | null;
            if (btn) btn.click();
        });

        await expect(page.locator('body')).not.toContainText('I-Düpferl-Reita', { useInnerText: true });
        await expect(page.locator('body')).not.toContainText('Replaced Text', { useInnerText: true });
        await expect(page.locator('#section_3_0')).toHaveCount(0);

        await test.step('add a reason', async () => {
            await page.locator('.sidebarActions .mergeamendments a').click();
            await page.waitForTimeout(1000);
            await expect(page.locator('#paragraphWrapper_2_0').first()).toBeVisible();
            await expect(page.locator('#sections_3_0_wysiwyg').first()).toBeVisible();

            const data = await page.evaluate(() => {
                const w = window as any;
                return w.CKEDITOR.instances.sections_3_0_wysiwyg.getData();
            });
            expect(data).toBe('');

            await page.evaluate(() => {
                const w = window as any;
                w.CKEDITOR.instances.sections_3_0_wysiwyg.setData('<p>Hi there!</p>');
            });

            await expect(page.locator('#sections_3_0_wysiwyg')).toContainText('Hi there!');

            await page.evaluate(() => {
                document.querySelectorAll('.none').forEach((el) => el.remove());
                document.querySelectorAll('#draftSavingPanel').forEach((el) => el.remove());
            });
            await page.waitForTimeout(1000);

            await page.locator('.motionMergeForm [name="save"]').click();

            await expect(page.locator('body')).toContainText('Hi there!');

            await page.locator('#motionConfirmForm [name="confirm"]').click();

            await page.evaluate(() => {
                const btn = document.querySelector('#motionConfirmedForm button') as HTMLElement | null;
                if (btn) btn.click();
            });

            await expect(page.locator('#section_3_0')).toContainText('Hi there!');
        });

    });
});
