import { test, expect } from '../../fixtures';
import {
    loginAsConsultationAdmin,
    loginAsProposalAdmin,
    loginAsStdAdmin,
    loginAsStdUser,
    logout,
} from '../../utils/auth';
import { setCkEditorContent } from '../../utils/dom';
import { FIRST_FREE_CONTENT_ID } from '../../utils/constants';

test.describe('Appearance: edit content pages', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create, edit, restrict, unpublish, delete a content page', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');

        await expect(page.locator('#mainmenu .addPage').filter({ visible: true })).toHaveCount(0);

        await loginAsConsultationAdmin(page);
        await expect(page.locator('#mainmenu .addPage').first()).toBeVisible();

        await page.locator('#mainmenu .addPage a').click();

        await test.step('create a new content page', async () => {
            await page.locator('.createPageForm #contentUrl').first().fill('about');
        });

        await test.step('edit the new page', async () => {
            await page.locator('.createPageForm #contentTitle').first().fill('About');
            await page.locator('.createPageForm [name="create"]').click();

            await expect(page.locator('h1')).toContainText('About');
            await expect(
                page.locator(`#mainmenu .page${FIRST_FREE_CONTENT_ID}`),
            ).toContainText('About');

            await page.locator('.editCaller').click();
            await page.waitForTimeout(1000);
        });

        await test.step('see the changes', async () => {
            await expect(page.locator('.contentSettingsToolbar').first()).toBeVisible();

            await setCkEditorContent(page, 'stdTextHolder', '<p>New text</p>');

            await page.locator('.contentSettingsToolbar #contentUrl').first().fill('images');
        });

        await page.locator('.contentSettingsToolbar #contentTitle').first().fill('Images');

        await expect(page.locator("input[name='inMenu']")).toBeChecked();
        await expect(page.locator('.userGroupSelect').filter({ visible: true })).toHaveCount(0);

        await page.locator('#policyReadPage').first().selectOption('3');
        await page.waitForTimeout(200);
        await expect(page.locator('.userGroupSelect').first()).toBeVisible();

        await page.evaluate(() => {
            const el = document.querySelector(
                '#policyReadPageGroups',
            ) as any;
            if (el && el.selectize) {
                el.selectize.addItem(3);
            }
        });
        const itemCount = await page.evaluate(() => {
            const el = document.querySelector('#policyReadPageGroups') as any;
            return el && el.selectize ? el.selectize.items.length : 0;
        });
        expect(itemCount).toBe(1);

        await page.locator('.submitBtn').click();
        await page.waitForTimeout(1000);

        await expect(page.locator('.contentSettingsToolbar').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('h1')).toContainText('Images');
        await expect(
            page.locator(`#mainmenu .page${FIRST_FREE_CONTENT_ID}`),
        ).toContainText('Images');
        await expect(page.locator('.content')).toContainText('New text');

        await logout(page);
        await loginAsStdUser(page);
        await expect(
            page.locator(`#mainmenu .page${FIRST_FREE_CONTENT_ID}`),
        ).not.toContainText('Images', { useInnerText: true });

        await page.goto('/stdparteitag/std-parteitag/pages/show-page?pageSlug=images');
        await expect(page.locator('body')).toContainText('Kein Zugriff auf diese Seite');
        await logout(page);

        await loginAsProposalAdmin(page);
        await expect(
            page.locator(`#mainmenu .page${FIRST_FREE_CONTENT_ID}`),
        ).toContainText('Images');
        await page.goto('/stdparteitag/std-parteitag/pages/show-page?pageSlug=images');
        await expect(page.locator('body')).toContainText('New text');

        await logout(page);
        await loginAsStdAdmin(page);

        await test.step('remove it from the menu', async () => {
            await page.locator('.editCaller').click();
            await page.waitForTimeout(1000);

            await page.evaluate(() => {
                const el = document.querySelector('#contentUrl') as HTMLElement;
                if (el) el.focus();
            });
            await page.waitForTimeout(500);

            await page.locator("input[name='allConsultations']").first().check();
            await page.locator("input[name='inMenu']").first().uncheck();
            await page.locator('.submitBtn').click();
            await page.waitForTimeout(1000);

            await expect(page.locator('h1')).toContainText('Images');
            await expect(
                page.locator(`#mainmenu .page${FIRST_FREE_CONTENT_ID}`),
            ).not.toContainText('Images', { useInnerText: true });
        });

        await test.step('delete it again', async () => {
            await page.locator('.deletePageForm button').click();
            await expect(page.locator('.bootbox')).toContainText('Diese Seite wirklich löschen?');
            await page.locator('.bootbox .btn-primary').first().click();

            await expect(page.locator('.createPageForm').first()).toBeVisible();
            await expect(page.locator('body')).not.toContainText('Images', { useInnerText: true });
            await expect(page.locator('body')).not.toContainText('About', { useInnerText: true });
        });

    });
});
